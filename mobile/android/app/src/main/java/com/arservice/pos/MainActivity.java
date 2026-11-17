package com.arservice.pos;

import android.annotation.SuppressLint;
import android.bluetooth.BluetoothAdapter;
import android.bluetooth.BluetoothDevice;
import android.bluetooth.BluetoothSocket;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.content.pm.PackageManager;
import android.os.Build;
import android.os.Bundle;
import android.os.VibrationEffect;
import android.os.Vibrator;
import android.print.PrintAttributes;
import android.print.PrintDocumentAdapter;
import android.print.PrintManager;
import android.util.Base64;
import android.webkit.JavascriptInterface;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.OutputStream;
import java.lang.reflect.Method;
import java.util.Set;
import java.util.UUID;

public class MainActivity extends AppCompatActivity {

    private WebView webView;
    private Vibrator vibrator;

    // Standard ESC/POS SPP UUID
    private static final UUID SPP_UUID = UUID.fromString("00001101-0000-1000-8000-00805F9B34FB");
    private BluetoothSocket bluetoothSocket;
    private OutputStream bluetoothOutputStream;
    private BroadcastReceiver discoveryReceiver;
    private boolean isReceiverRegistered = false;

    @SuppressLint("SetJavaScriptEnabled")
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        webView = new WebView(this);
        setContentView(webView);

        vibrator = (Vibrator) getSystemService(Context.VIBRATOR_SERVICE);

        WebSettings settings = webView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setDatabaseEnabled(true);
        settings.setAllowFileAccess(true);
        settings.setAllowContentAccess(true);
        settings.setAllowFileAccessFromFileURLs(true);
        settings.setAllowUniversalAccessFromFileURLs(true);
        settings.setLoadWithOverviewMode(true);
        settings.setUseWideViewPort(true);
        settings.setBuiltInZoomControls(false);
        settings.setDisplayZoomControls(false);
        settings.setSupportZoom(false);
        settings.setCacheMode(WebSettings.LOAD_NO_CACHE);
        webView.clearCache(true);

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
            settings.setMixedContentMode(WebSettings.MIXED_CONTENT_ALWAYS_ALLOW);
        }

        webView.setWebChromeClient(new WebChromeClient());
        webView.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                String url = request.getUrl().toString();
                if (url.startsWith("https://wa.me/") || url.startsWith("whatsapp://")) {
                    try {
                        Intent intent = new Intent(Intent.ACTION_VIEW, request.getUrl());
                        startActivity(intent);
                        return true;
                    } catch (Exception e) {
                        Toast.makeText(MainActivity.this, "WhatsApp tidak terpasang", Toast.LENGTH_SHORT).show();
                        return true;
                    }
                } else if (url.startsWith("rawbt:") || url.startsWith("intent:")) {
                    try {
                        Intent intent = Intent.parseUri(url, Intent.URI_INTENT_SCHEME);
                        startActivity(intent);
                        return true;
                    } catch (Exception e) {
                        Toast.makeText(MainActivity.this, "Aplikasi cetak tidak tersedia", Toast.LENGTH_SHORT).show();
                        return true;
                    }
                }
                return false;
            }
        });

        // Add JavaScript Bridge
        webView.addJavascriptInterface(new AndroidBridge(), "AndroidBridge");

        // Request Bluetooth permissions on Android 12+ / 6+
        checkBluetoothPermissions();

        // Load bundled offline assets
        webView.loadUrl("file:///android_asset/www/index.html");
    }

    private void checkBluetoothPermissions() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            if (checkSelfPermission(android.Manifest.permission.BLUETOOTH_CONNECT) != PackageManager.PERMISSION_GRANTED ||
                checkSelfPermission(android.Manifest.permission.BLUETOOTH_SCAN) != PackageManager.PERMISSION_GRANTED) {
                requestPermissions(new String[]{
                    android.Manifest.permission.BLUETOOTH_CONNECT,
                    android.Manifest.permission.BLUETOOTH_SCAN,
                    android.Manifest.permission.ACCESS_FINE_LOCATION
                }, 1001);
            }
        } else if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            if (checkSelfPermission(android.Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED) {
                requestPermissions(new String[]{
                    android.Manifest.permission.ACCESS_FINE_LOCATION
                }, 1001);
            }
        }
    }

    public class AndroidBridge {

        @JavascriptInterface
        public void showToast(final String message) {
            runOnUiThread(() -> Toast.makeText(MainActivity.this, message, Toast.LENGTH_SHORT).show());
        }

        @JavascriptInterface
        public void vibrate(long milliseconds) {
            if (vibrator != null && vibrator.hasVibrator()) {
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                    vibrator.vibrate(VibrationEffect.createOneShot(milliseconds, VibrationEffect.DEFAULT_AMPLITUDE));
                } else {
                    vibrator.vibrate(milliseconds);
                }
            }
        }

        @JavascriptInterface
        public void shareText(final String text) {
            runOnUiThread(() -> {
                Intent sendIntent = new Intent();
                sendIntent.setAction(Intent.ACTION_SEND);
                sendIntent.putExtra(Intent.EXTRA_TEXT, text);
                sendIntent.setType("text/plain");
                Intent shareIntent = Intent.createChooser(sendIntent, "Bagikan Nota");
                startActivity(shareIntent);
            });
        }

        @JavascriptInterface
        public void printHtml(final String html) {
            runOnUiThread(() -> {
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.KITKAT) {
                    PrintManager printManager = (PrintManager) getSystemService(Context.PRINT_SERVICE);
                    if (printManager != null) {
                        PrintDocumentAdapter printAdapter = webView.createPrintDocumentAdapter("Nota_Penjualan");
                        printManager.print("Nota POS AR Service", printAdapter, new PrintAttributes.Builder().build());
                    }
                } else {
                    Toast.makeText(MainActivity.this, "Pencetakan tidak didukung di versi Android ini", Toast.LENGTH_SHORT).show();
                }
            });
        }

        // --- Native Bluetooth Printer Bridge ---

        @JavascriptInterface
        public String getPairedDevices() {
            JSONArray arr = new JSONArray();
            try {
                BluetoothAdapter adapter = BluetoothAdapter.getDefaultAdapter();
                if (adapter != null && adapter.isEnabled()) {
                    Set<BluetoothDevice> paired = adapter.getBondedDevices();
                    if (paired != null) {
                        for (BluetoothDevice dev : paired) {
                            JSONObject obj = new JSONObject();
                            obj.put("name", dev.getName() != null ? dev.getName() : "Printer Bluetooth");
                            obj.put("address", dev.getAddress());
                            obj.put("type", "paired");
                            arr.put(obj);
                        }
                    }
                }
            } catch (Exception e) {
                e.printStackTrace();
            }
            return arr.toString();
        }

        @JavascriptInterface
        public void startDiscovery() {
            runOnUiThread(() -> {
                try {
                    checkBluetoothPermissions();
                    BluetoothAdapter adapter = BluetoothAdapter.getDefaultAdapter();
                    if (adapter == null || !adapter.isEnabled()) {
                        Toast.makeText(MainActivity.this, "Bluetooth belum aktif. Silakan aktifkan Bluetooth HP.", Toast.LENGTH_LONG).show();
                        webView.evaluateJavascript("if (window.onAndroidBluetoothDiscoveryFinished) window.onAndroidBluetoothDiscoveryFinished();", null);
                        return;
                    }

                    if (adapter.isDiscovering()) {
                        adapter.cancelDiscovery();
                    }

                    if (isReceiverRegistered && discoveryReceiver != null) {
                        try { unregisterReceiver(discoveryReceiver); } catch (Exception ignored) {}
                        isReceiverRegistered = false;
                    }

                    discoveryReceiver = new BroadcastReceiver() {
                        @Override
                        public void onReceive(Context context, Intent intent) {
                            String action = intent.getAction();
                            if (BluetoothDevice.ACTION_FOUND.equals(action)) {
                                BluetoothDevice dev = intent.getParcelableExtra(BluetoothDevice.EXTRA_DEVICE);
                                if (dev != null && dev.getAddress() != null) {
                                    String name = dev.getName() != null ? dev.getName() : "Perangkat Sekitar";
                                    String addr = dev.getAddress();
                                    String js = String.format("if (window.onAndroidBluetoothDeviceFound) window.onAndroidBluetoothDeviceFound('%s', '%s');",
                                            name.replace("'", "\\'"), addr.replace("'", "\\'"));
                                    webView.evaluateJavascript(js, null);
                                }
                            } else if (BluetoothAdapter.ACTION_DISCOVERY_FINISHED.equals(action)) {
                                webView.evaluateJavascript("if (window.onAndroidBluetoothDiscoveryFinished) window.onAndroidBluetoothDiscoveryFinished();", null);
                            }
                        }
                    };

                    IntentFilter filter = new IntentFilter();
                    filter.addAction(BluetoothDevice.ACTION_FOUND);
                    filter.addAction(BluetoothAdapter.ACTION_DISCOVERY_FINISHED);
                    registerReceiver(discoveryReceiver, filter);
                    isReceiverRegistered = true;

                    adapter.startDiscovery();
                } catch (Exception e) {
                    e.printStackTrace();
                    webView.evaluateJavascript("if (window.onAndroidBluetoothDiscoveryFinished) window.onAndroidBluetoothDiscoveryFinished();", null);
                }
            });
        }

        @JavascriptInterface
        public void stopDiscovery() {
            try {
                BluetoothAdapter adapter = BluetoothAdapter.getDefaultAdapter();
                if (adapter != null && adapter.isDiscovering()) {
                    adapter.cancelDiscovery();
                }
                if (isReceiverRegistered && discoveryReceiver != null) {
                    unregisterReceiver(discoveryReceiver);
                    isReceiverRegistered = false;
                }
            } catch (Exception ignored) {}
        }

        @JavascriptInterface
        public void connectPrinter(final String address) {
            new Thread(() -> {
                BluetoothAdapter adapter = BluetoothAdapter.getDefaultAdapter();
                if (adapter == null) {
                    notifyConnectResult(false, "", "Bluetooth tidak tersedia");
                    return;
                }
                if (adapter.isDiscovering()) {
                    adapter.cancelDiscovery();
                }
                try {
                    disconnectPrinterInternal();
                    BluetoothDevice device = adapter.getRemoteDevice(address);
                    BluetoothSocket socket = null;

                    // 1. Try standard SPP UUID
                    try {
                        socket = device.createRfcommSocketToServiceRecord(SPP_UUID);
                        socket.connect();
                    } catch (Exception e1) {
                        // 2. Fallback via reflection for stubborn Bluetooth Classic thermal printers
                        try {
                            Method m = device.getClass().getMethod("createRfcommSocket", new Class[]{int.class});
                            socket = (BluetoothSocket) m.invoke(device, 1);
                            if (socket != null) {
                                socket.connect();
                            }
                        } catch (Exception e2) {
                            throw new Exception("Gagal pairing socket printer: " + e2.getMessage());
                        }
                    }

                    if (socket != null && socket.isConnected()) {
                        bluetoothSocket = socket;
                        bluetoothOutputStream = socket.getOutputStream();
                        final String devName = device.getName() != null ? device.getName() : address;
                        notifyConnectResult(true, devName, address);
                    } else {
                        notifyConnectResult(false, "", "Koneksi printer gagal dibuat");
                    }
                } catch (Exception e) {
                    e.printStackTrace();
                    notifyConnectResult(false, "", e.getMessage() != null ? e.getMessage() : "Gagal menyambung");
                }
            }).start();
        }

        @JavascriptInterface
        public void disconnectPrinter() {
            disconnectPrinterInternal();
            notifyConnectResult(false, "", "Terputus");
        }

        @JavascriptInterface
        public boolean isPrinterConnected() {
            return bluetoothSocket != null && bluetoothSocket.isConnected();
        }

        @JavascriptInterface
        public boolean printEscPos(final String base64Data) {
            try {
                if (bluetoothOutputStream == null || bluetoothSocket == null || !bluetoothSocket.isConnected()) {
                    return false;
                }
                byte[] bytes = Base64.decode(base64Data, Base64.DEFAULT);
                bluetoothOutputStream.write(bytes);
                bluetoothOutputStream.flush();
                return true;
            } catch (Exception e) {
                e.printStackTrace();
                return false;
            }
        }

        private void notifyConnectResult(final boolean success, final String name, final String info) {
            runOnUiThread(() -> {
                String js = String.format("if (window.onAndroidBluetoothConnected) window.onAndroidBluetoothConnected(%s, '%s', '%s');",
                        success ? "true" : "false",
                        name.replace("'", "\\'"),
                        info.replace("'", "\\'"));
                webView.evaluateJavascript(js, null);
            });
        }

        private void disconnectPrinterInternal() {
            try {
                if (bluetoothOutputStream != null) {
                    bluetoothOutputStream.close();
                    bluetoothOutputStream = null;
                }
                if (bluetoothSocket != null) {
                    bluetoothSocket.close();
                    bluetoothSocket = null;
                }
            } catch (Exception ignored) {}
        }
    }

    @Override
    protected void onDestroy() {
        if (isReceiverRegistered && discoveryReceiver != null) {
            try { unregisterReceiver(discoveryReceiver); } catch (Exception ignored) {}
            isReceiverRegistered = false;
        }
        if (bluetoothOutputStream != null) {
            try { bluetoothOutputStream.close(); } catch (Exception ignored) {}
        }
        if (bluetoothSocket != null) {
            try { bluetoothSocket.close(); } catch (Exception ignored) {}
        }
        super.onDestroy();
    }

    @Override
    public void onBackPressed() {
        if (webView.canGoBack()) {
            webView.goBack();
        } else {
            super.onBackPressed();
        }
    }
}
