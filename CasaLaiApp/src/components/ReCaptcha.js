import React, { forwardRef, useEffect, useImperativeHandle, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Linking,
  Modal,
  Platform,
  StyleSheet,
  Text,
  TouchableOpacity,
  TouchableWithoutFeedback,
  View,
} from 'react-native';
import { WebView } from 'react-native-webview';
import { API_BASE_URL } from '../config/apiConfig';

const isDevelopmentBuild = typeof __DEV__ !== 'undefined' && __DEV__;
const configuredRecaptchaDomain = process.env.EXPO_PUBLIC_RECAPTCHA_DOMAIN;
const RECAPTCHA_DOMAIN = isDevelopmentBuild && configuredRecaptchaDomain
  ? configuredRecaptchaDomain
  : 'casalai.alwaysdata.net';
const RECAPTCHA_ORIGIN = /^https?:\/\//i.test(RECAPTCHA_DOMAIN)
  ? RECAPTCHA_DOMAIN.replace(/\/+$/, '')
  : `https://${RECAPTCHA_DOMAIN}`;

const ReCaptcha = forwardRef(({ onVerify, onError }, ref) => {
  const [siteKey, setSiteKey] = useState('');
  const [configurationError, setConfigurationError] = useState('');
  const [serviceReady, setServiceReady] = useState(false);
  const [verified, setVerified] = useState(false);
  const [modalVisible, setModalVisible] = useState(false);
  const [nativeReady, setNativeReady] = useState(false);
  const webViewRef = useRef(null);
  const webWidgetIdRef = useRef(null);
  const onVerifyRef = useRef(onVerify);
  const onErrorRef = useRef(onError);

  useEffect(() => {
    onVerifyRef.current = onVerify;
    onErrorRef.current = onError;
  }, [onError, onVerify]);

  useEffect(() => {
    let active = true;

    const loadSiteKey = async () => {
      try {
        const response = await fetch(`${API_BASE_URL}/recaptcha_config.php`, {
          headers: { Accept: 'application/json' },
        });
        const result = await response.json();

        if (!response.ok) {
          throw new Error(result?.message || 'No se pudo cargar la configuración de reCAPTCHA.');
        }
        if (typeof result?.site_key !== 'string' || !result.site_key.trim()) {
          throw new Error('El API no devolvió una site key de reCAPTCHA válida.');
        }

        if (active) setSiteKey(result.site_key.trim());
      } catch (error) {
        console.error('[reCAPTCHA] Error de configuración:', {
          message: error.message,
          configUrl: `${API_BASE_URL}/recaptcha_config.php`,
          origin: typeof window !== 'undefined' ? window.location.origin : null,
        });
        if (active) {
          setConfigurationError(
            'No se pudo conectar con la API para cargar reCAPTCHA. Verifica XAMPP, la URL local y CORS.'
          );
          onErrorRef.current?.();
        }
      }
    };

    loadSiteKey();
    return () => {
      active = false;
    };
  }, []);

  useEffect(() => {
    if (Platform.OS !== 'web' || !siteKey) return undefined;

    const container = document.getElementById('casalai-recaptcha-widget');
    if (!container) {
      setConfigurationError('No se pudo mostrar el cuadro de reCAPTCHA.');
      onErrorRef.current?.();
      return undefined;
    }

    let active = true;
    const renderWidget = () => {
      if (!active || !window.grecaptcha?.render || webWidgetIdRef.current !== null) return;
      try {
        webWidgetIdRef.current = window.grecaptcha.render(container, {
          sitekey: siteKey,
          callback: (token) => receiveToken(token),
          'expired-callback': expireToken,
          'error-callback': failVerification,
        });
        setServiceReady(true);
      } catch (error) {
        console.error('[reCAPTCHA] Error al mostrar el cuadro:', error.message);
        setConfigurationError('No se pudo mostrar el cuadro de reCAPTCHA.');
        onErrorRef.current?.();
      }
    };

    if (window.grecaptcha?.render) {
      renderWidget();
    } else {
      const callbackName = 'onCasaLaiRecaptchaLoaded';
      window[callbackName] = renderWidget;
      const script = document.createElement('script');
      script.src = `https://www.google.com/recaptcha/api.js?onload=${callbackName}&render=explicit`;
      script.async = true;
      script.defer = true;
      script.onerror = () => {
        if (!active) return;
        setConfigurationError('No se pudo cargar el servicio de reCAPTCHA de Google.');
        onErrorRef.current?.();
      };
      document.head.appendChild(script);
    }

    return () => {
      active = false;
      webWidgetIdRef.current = null;
      delete window.onCasaLaiRecaptchaLoaded;
    };
  }, [siteKey]);

  const receiveToken = (token) => {
    if (typeof token !== 'string' || !token) {
      failVerification();
      return;
    }

    setConfigurationError('');
    setVerified(true);
    onVerifyRef.current?.(token);
  };

  const expireToken = () => {
    setVerified(false);
    onVerifyRef.current?.(null);
  };

  const failVerification = () => {
    setVerified(false);
    setConfigurationError('No se pudo completar reCAPTCHA. Inténtalo de nuevo.');
    onVerifyRef.current?.(null);
    onErrorRef.current?.();
  };

  const failNativeVerification = () => {
    setModalVisible(false);
    setNativeReady(false);
    failVerification();
  };

  useImperativeHandle(ref, () => ({
    reset: () => {
      setVerified(false);
      setConfigurationError('');
      onVerifyRef.current?.(null);
      if (Platform.OS === 'web' && webWidgetIdRef.current !== null) {
        window.grecaptcha?.reset(webWidgetIdRef.current);
      } else {
        setModalVisible(false);
        setNativeReady(false);
        webViewRef.current?.reload();
      }
    },
  }), []);

  const handleVerifyPress = () => {
    if (!siteKey) return;
    setVerified(false);
    setConfigurationError('');
    onVerifyRef.current?.(null);
    setNativeReady(false);
    setModalVisible(true);
  };

  const nativeHtml = `
    <!doctype html>
    <html>
      <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
          html, body { margin: 0; padding: 4px; background: #fff; }
          body { display: flex; justify-content: center; }
        </style>
        <script>
          function send(type, token) {
            window.ReactNativeWebView.postMessage(JSON.stringify({ type: type, token: token || null }));
          }
          function onCasaLaiRecaptchaLoaded() {
            try {
              grecaptcha.render('recaptcha-widget', {
                sitekey: ${JSON.stringify(siteKey)},
                callback: function(token) { send('TOKEN', token); },
                'expired-callback': function() { send('EXPIRED'); },
                'error-callback': function() { send('ERROR'); }
              });
              send('READY');
            } catch (error) {
              send('ERROR');
            }
          }
        </script>
        <script
          src="https://www.google.com/recaptcha/api.js?onload=onCasaLaiRecaptchaLoaded&render=explicit"
          async defer>
        </script>
      </head>
      <body><div id="recaptcha-widget"></div></body>
    </html>
  `;

  const handleNativeMessage = (event) => {
    try {
      const message = JSON.parse(event.nativeEvent.data);
      if (message.type === 'READY') {
        setNativeReady(true);
      } else if (message.type === 'TOKEN') {
        receiveToken(message.token);
        setModalVisible(false);
      } else if (message.type === 'EXPIRED') {
        expireToken();
      } else if (message.type === 'ERROR') {
        failNativeVerification();
      }
    } catch (error) {
      console.error('[reCAPTCHA] Respuesta inválida de WebView:', error.message);
      failNativeVerification();
    }
  };

  const closeNativeModal = () => {
    setModalVisible(false);
    setNativeReady(false);
    expireToken();
  };

  return (
    <View style={styles.container}>
      {!siteKey ? (
        <Text style={styles.statusText}>
          {configurationError || 'Cargando reCAPTCHA…'}
        </Text>
      ) : Platform.OS === 'web' ? (
        <>
          {!serviceReady ? <ActivityIndicator color="#1a73e8" /> : null}
          <View nativeID="casalai-recaptcha-widget" style={styles.webWidget} />
        </>
      ) : (
        <TouchableOpacity
          accessibilityRole="button"
          style={[styles.verifyButton, verified && styles.verifyButtonComplete]}
          onPress={handleVerifyPress}
          activeOpacity={0.8}
        >
          <Text style={styles.verifyButtonText}>
            {verified ? 'Verificación completada' : 'Verificar que no soy un robot'}
          </Text>
        </TouchableOpacity>
      )}
      <Text style={styles.disclosure}>
        Protegido por reCAPTCHA. Se aplican la{' '}
        <Text
          style={styles.linkText}
          onPress={() => Linking.openURL('https://policies.google.com/privacy')}
        >
          Política de privacidad
        </Text> y los{' '}
        <Text
          style={styles.linkText}
          onPress={() => Linking.openURL('https://policies.google.com/terms')}
        >
          Términos del servicio
        </Text> de Google.
      </Text>
      {configurationError ? (
        <Text style={styles.statusText}>{configurationError}</Text>
      ) : null}
      {Platform.OS !== 'web' && (
        <Modal
          visible={modalVisible}
          transparent
          animationType="fade"
          onRequestClose={closeNativeModal}
        >
          <TouchableOpacity
            style={styles.modalOverlay}
            activeOpacity={1}
            onPress={closeNativeModal}
          >
            <TouchableWithoutFeedback>
              <View style={styles.modalContent}>
                {!nativeReady && <ActivityIndicator size="large" color="#2196F3" />}
                <WebView
                  ref={webViewRef}
                  originWhitelist={['*']}
                  source={{ html: nativeHtml, baseUrl: `${RECAPTCHA_ORIGIN}/` }}
                  onMessage={handleNativeMessage}
                  onError={failNativeVerification}
                  javaScriptEnabled
                  domStorageEnabled
                  style={styles.webview}
                />
                <TouchableOpacity style={styles.closeButton} onPress={closeNativeModal}>
                  <Text style={styles.closeButtonText}>Cancelar</Text>
                </TouchableOpacity>
              </View>
            </TouchableWithoutFeedback>
          </TouchableOpacity>
        </Modal>
      )}
    </View>
  );
});

const styles = StyleSheet.create({
  container: {
    width: '100%',
    marginVertical: 12,
    alignItems: 'center',
  },
  webWidget: {
    width: 304,
    minHeight: 78,
    alignSelf: 'center',
  },
  verifyButton: {
    minHeight: 48,
    width: '100%',
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    backgroundColor: '#1a73e8',
    paddingHorizontal: 14,
  },
  verifyButtonComplete: {
    backgroundColor: '#188038',
  },
  verifyButtonText: {
    color: '#ffffff',
    fontSize: 15,
    fontWeight: '600',
    textAlign: 'center',
  },
  statusText: {
    color: '#b91c1c',
    fontSize: 12,
    textAlign: 'center',
    marginTop: 6,
  },
  disclosure: {
    color: '#5f6368',
    fontSize: 11,
    textAlign: 'center',
    marginTop: 8,
  },
  linkText: {
    color: '#1a73e8',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.6)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalContent: {
    width: 340,
    minHeight: 190,
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 10,
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  webview: {
    flex: 1,
    minHeight: 90,
    width: '100%',
    backgroundColor: 'transparent',
  },
  closeButton: {
    paddingVertical: 10,
    paddingHorizontal: 20,
  },
  closeButtonText: {
    color: '#2196F3',
    fontSize: 16,
  },
});

export default ReCaptcha;
