import React, { useEffect, useMemo, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ScrollView,
  Alert,
  ActivityIndicator,
  Modal,
  TextInput,
  Image,
} from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import { Ionicons } from '@expo/vector-icons';
import { useNavigation, useRoute } from '@react-navigation/native';
import COLORS from '../../constants/colors';
import { API_BASE_URL, PAGOS_IMAGE_STORAGE_PATH, buildPaymentImageUrl } from '../../config/apiConfig';
import { getStoredToken } from '../../utils/jwt';
import { InvocarMetodo } from '../../utils/comunicadorApi';
import { BANCOS_VENEZUELA, limpiarCampoPago, validarFormularioPago, parsearNumeroFormateado } from '../../validation/pagoValidation';
import { formatPhoneNumber } from '../../validation/profileValidation';

const PAYMENT_METHOD_ORDER = ['Pago Movil', 'Transferencia', 'Zelle'];

const formatCurrency = (value) => {
  const numericValue = Number(value || 0);
  return new Intl.NumberFormat('es-VE', {
    style: 'currency',
    currency: 'VES',
    minimumFractionDigits: 2,
  }).format(numericValue);
};

const normalizeAccounts = (payload) => {
  if (!payload) return [];

  let rawList = [];
  if (Array.isArray(payload)) rawList = payload;
  else if (Array.isArray(payload?.data)) rawList = payload.data;
  else if (Array.isArray(payload?.data?.cuentas)) rawList = payload.data.cuentas;
  else if (Array.isArray(payload?.cuentas)) rawList = payload.cuentas;
  else if (Array.isArray(payload?.data?.data?.cuentas)) rawList = payload.data.data.cuentas;

  return rawList
    .map((item) => {
      const metodos = typeof item.metodos === 'string'
        ? item.metodos.split(',').map((method) => method.trim()).filter(Boolean)
        : Array.isArray(item.metodos)
          ? item.metodos.filter(Boolean)
          : [];

      return {
        id_cuenta: item.id_cuenta ?? item.id ?? `${item.nombre_banco ?? 'cuenta'}-${item.numero_cuenta ?? Math.random()}`,
        nombre_banco: item.nombre_banco ?? 'Cuenta bancaria',
        numero_cuenta: item.numero_cuenta ?? '',
        rif_cuenta: item.rif_cuenta ?? '',
        telefono_cuenta: item.telefono_cuenta ?? '',
        correo_cuenta: item.correo_cuenta ?? '',
        metodos,
        estado: String(item.estado ?? 'habilitado').toLowerCase(),
      };
    })
    .filter((item) => item.estado === 'habilitado');
};

const buildMethodMap = (accounts) => {
  const grouped = {};
  PAYMENT_METHOD_ORDER.forEach((method) => {
    grouped[method] = [];
  });

  accounts.forEach((account) => {
    const methods = Array.isArray(account.metodos) && account.metodos.length
      ? account.metodos
      : ['Transferencia'];

    methods.forEach((method) => {
      if (PAYMENT_METHOD_ORDER.includes(method)) {
        grouped[method].push(account);
      }
    });
  });

  return grouped;
};

const getPaymentFormFields = (method, cuenta, total) => {
  const baseAmount = Number(total || 0);

  switch (method) {
    case 'Pago Movil':
      return [
        { key: 'referencia', label: 'Referencia', placeholder: 'Ej. 123456', keyboardType: 'number-pad', maxLength: 20 },
        { key: 'telefono', label: 'Teléfono del remitente', placeholder: 'Formato: 0400-000-0000', keyboardType: 'phone-pad', maxLength: 15 },
        { key: 'monto', label: 'Monto pagado', placeholder: String(baseAmount.toFixed(2)), keyboardType: 'decimal-pad' },
      ];
    case 'Transferencia':
      return [
        { key: 'banco_origen', label: 'Banco de origen', placeholder: 'Ej. Banesco', keyboardType: 'default', maxLength: 50 },
        { key: 'referencia', label: 'Referencia', placeholder: 'Ej. 123456', keyboardType: 'number-pad', maxLength: 30 },
        { key: 'monto', label: 'Monto transferido', placeholder: String(baseAmount.toFixed(2)), keyboardType: 'decimal-pad' },
      ];
    case 'Zelle':
      return [
        { key: 'remitente', label: 'Nombre del remitente', placeholder: 'Ej. Ana García', keyboardType: 'default', maxLength: 80 },
        { key: 'referencia', label: 'Referencia', placeholder: 'Ej. 123456', keyboardType: 'number-pad', maxLength: 30 },
        { key: 'monto', label: 'Monto enviado', placeholder: String(baseAmount.toFixed(2)), keyboardType: 'decimal-pad' },
      ];
    default:
      return [];
  }
};


const PaymentScreen = () => {
  const navigation = useNavigation();
  const route = useRoute();
  const pedido = route.params?.pedido || null;

  const [cuentas, setCuentas] = useState([]);
  const [selectedMethod, setSelectedMethod] = useState('');
  const [selectedCuenta, setSelectedCuenta] = useState(null);
  const [loading, setLoading] = useState(true);
  const [showPaymentForm, setShowPaymentForm] = useState(false);
  const [paymentSuccessVisible, setPaymentSuccessVisible] = useState(false);
  const [paymentSuccessMessage, setPaymentSuccessMessage] = useState('');
  const [paymentForm, setPaymentForm] = useState({
    referencia: '',
    monto: '',
    telefono: '',
    banco_origen: '',
    remitente: '',
    comprobante: '',
    comprobanteName: '',
  });
  const [validationErrors, setValidationErrors] = useState([]);
  const [bankSelectorVisible, setBankSelectorVisible] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const methodGroups = useMemo(() => buildMethodMap(cuentas), [cuentas]);
  const availableMethods = PAYMENT_METHOD_ORDER.filter((method) => (methodGroups[method]?.length || 0) > 0);
  const paymentFormFields = useMemo(() => getPaymentFormFields(selectedMethod, selectedCuenta, pedido?.total || pedido?.monto || 0), [selectedMethod, selectedCuenta, pedido]);

  useEffect(() => {
    const obtenerCuentas = async () => {
      try {
        setLoading(true);
        const response = await InvocarMetodo('cuentas.php', 'default', {});
        const data = normalizeAccounts(response);
        setCuentas(data);

        if (data.length > 0) {
          const firstMethod = availableMethods[0] || 'Transferencia';
          setSelectedMethod(firstMethod);
          setSelectedCuenta(data.find((cuenta) => cuenta.metodos.includes(firstMethod)) || data[0]);
        }
      } catch (error) {
        Alert.alert('Error', error?.message || 'No se pudieron cargar las cuentas bancarias.');
      } finally {
        setLoading(false);
      }
    };

    obtenerCuentas();
  }, []);

  useEffect(() => {
    if (!selectedMethod && availableMethods.length > 0) {
      setSelectedMethod(availableMethods[0]);
    }
  }, [availableMethods, selectedMethod]);

  useEffect(() => {
    if (!selectedMethod) return;

    const candidates = methodGroups[selectedMethod] || [];
    if (!selectedCuenta || !candidates.some((cuenta) => cuenta.id_cuenta === selectedCuenta.id_cuenta)) {
      setSelectedCuenta(candidates[0] || null);
    }
  }, [selectedMethod, methodGroups, selectedCuenta]);

  const resetPaymentForm = () => {
    const defaultAmount = String((pedido?.total || pedido?.monto || 0).toFixed(2));

    setPaymentForm({
      referencia: '',
      monto: defaultAmount,
      telefono: '',
      banco_origen: '',
      remitente: '',
      comprobante: '',
      comprobanteName: '',
    });
    setValidationErrors([]);
  };

  const navigateToPedidos = () => {
    try {
      navigation.navigate('Main', { screen: 'Pedidos' });
    } catch (error) {
      try {
        navigation.navigate('Pedidos');
      } catch (navError) {
        navigation.goBack();
      }
    }
  };

  const handleConfirmPayment = () => {
    if (!selectedMethod || !selectedCuenta) {
      Alert.alert('Atención', 'Seleccione un método de pago y una cuenta bancaria.');
      return;
    }

    resetPaymentForm();
    setShowPaymentForm(true);
  };

  const handlePickComprobante = async () => {
    try {
      const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
      if (!permission.granted) {
        Alert.alert('Permiso requerido', 'Necesitas permitir acceso a la galería para adjuntar el comprobante.');
        return;
      }

      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ImagePicker.MediaTypeOptions.Images,
        allowsEditing: true,
        quality: 0.8,
      });

      if (result.canceled || !result.assets?.[0]) {
        return;
      }

      const asset = result.assets[0];
      setPaymentForm((previous) => ({
        ...previous,
        comprobante: asset.uri,
        comprobanteName: asset.fileName || 'comprobante.jpg',
      }));
    } catch (error) {
      Alert.alert('Error', error?.message || 'No se pudo adjuntar el comprobante.');
    }
  };

  const handleSubmitPaymentForm = async () => {
    if (!selectedMethod || !selectedCuenta) {
      Alert.alert('Error', 'Debe seleccionar un método de pago y cuenta.');
      return;
    }

    // Ejecución de la validación exportada desde src/validation/pagoValidation.js
    const erroresValidacion = validarFormularioPago(paymentForm, selectedMethod, pedido);
    setValidationErrors(erroresValidacion);
    if (erroresValidacion.length > 0) {
      return;
    }

    const facturaId = pedido?.id_factura ?? pedido?.id ?? pedido?.numero ?? null;
    const cuentaId = selectedCuenta?.id_cuenta ?? null;

    if (!facturaId || !cuentaId) {
      Alert.alert('Datos incompletos', 'No se pudo identificar la factura o cuenta.');
      return;
    }

    setIsSubmitting(true);

    const montoNum = parsearNumeroFormateado(paymentForm.monto);
    const refClean = paymentForm.referencia.trim();

    let detalleObservaciones = '';
    if (selectedMethod === 'Pago Movil') {
      detalleObservaciones = `Tel. Pagador: ${paymentForm.telefono.trim()}`;
    } else if (selectedMethod === 'Transferencia') {
      detalleObservaciones = `Banco Origen: ${paymentForm.banco_origen.trim()}`;
    } else if (selectedMethod === 'Zelle') {
      detalleObservaciones = `Remitente: ${paymentForm.remitente.trim()}`;
    }

    try {
      const fileUri = paymentForm.comprobante;
      const originalFileName = paymentForm.comprobanteName || fileUri?.split('/').pop() || 'comprobante.jpg';
      const cleanFileName = originalFileName.replace(/\\/g, '/').split('/').pop() || 'comprobante.jpg';
      const extensionFromName = cleanFileName.includes('.') ? cleanFileName.split('.').pop().toLowerCase() : 'jpg';
      const safeBaseName = cleanFileName
        .replace(/\.[^/.]+$/, '')
        .replace(/[^a-zA-Z0-9_-]/g, '_') || 'comprobante';
      const uploadFileName = `${Date.now()}_${safeBaseName}.${extensionFromName || 'jpg'}`;

      const fileResponse = await fetch(fileUri);
      if (!fileResponse.ok) {
        throw new Error('No se pudo leer el comprobante seleccionado.');
      }

      const imageBlob = await fileResponse.blob();
      if (!imageBlob.size) {
        throw new Error('El comprobante seleccionado está vacío.');
      }

      const normalizedStoragePath = (PAGOS_IMAGE_STORAGE_PATH || 'assets/img/uploads/comprobantes/')
        .replace(/\\/g, '/')
        .replace(/^\/+|\/+$/g, '');

      const formData = new FormData();
      formData.append('funcion', 'pago_ingresar');
      formData.append('factura', String(facturaId));
      formData.append('cuenta', String(cuentaId));
      formData.append('tipo', String(selectedMethod));
      formData.append('monto', String(montoNum));
      formData.append('referencia', refClean);
      formData.append('fecha', new Date().toISOString().slice(0, 10));
      formData.append('observaciones', detalleObservaciones);
      // formData.append('ruta_comprobante', normalizedStoragePath);
      formData.append('comprobante', imageBlob, uploadFileName);

      const token = await getStoredToken();
      if (!token) {
        throw new Error('Tu sesión expiró. Inicia sesión nuevamente.');
      }

      const response = await fetch(`${API_BASE_URL}/pagos.php`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
        body: formData,
      });

      const contentType = response.headers.get('content-type') || '';
      const result = contentType.includes('application/json') ? await response.json() : await response.text();
      const nestedData = result && typeof result === 'object' && result.data ? result.data : result;

      const successStatus =
        response?.ok && (
          result?.status === 'success' ||
          nestedData?.status === 'success' ||
          result?.success === true ||
          nestedData?.success === true ||
          !!(nestedData?.pago_id || nestedData?.factura_id)
        );

      if (!successStatus) {
        throw new Error(result?.message || nestedData?.message || 'Error al procesar el pago.');
      }

      setShowPaymentForm(false);
      const msgExito = result?.message || nestedData?.message || 'El pago fue registrado de forma exitosa, solo debe esperar a que sea verificado por el administrador.';
      setPaymentSuccessMessage(msgExito === 'Operación exitosa' ? 'El pago fue registrado de forma exitosa, solo debe esperar a que sea verificado por el administrador.' : msgExito);
      setPaymentSuccessVisible(true);
    } catch (error) {
      console.error('[Payment] Error registrando pago:', error);
      Alert.alert('No se pudo registrar el pago', error?.message || 'Verifique los datos e intente nuevamente.');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (loading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color={COLORS.primary} />
        <Text style={styles.loadingText}>Cargando métodos de pago...</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <ScrollView contentContainerStyle={styles.content} showsVerticalScrollIndicator={false}>
        <View style={styles.orderCard}>
          <Text style={styles.sectionTitle}>Pedido seleccionado</Text>
          <View style={styles.orderRow}>
            <Text style={styles.orderLabel}>Factura</Text>
            <Text style={styles.orderValue}>{pedido?.numero || pedido?.numero_factura || 'Sin número'}</Text>
          </View>
          <View style={styles.orderRow}>
            <Text style={styles.orderLabel}>Estado</Text>
            <Text style={styles.orderValue}>{pedido?.estado || 'Borrador'}</Text>
          </View>
          <View style={styles.orderRow}>
            <Text style={styles.orderLabel}>Total</Text>
            <Text style={styles.orderValue}>{formatCurrency(pedido?.total || pedido?.monto || 0)}</Text>
          </View>
        </View>

        <Text style={styles.sectionTitle}>Elige un método de pago</Text>
        <View style={styles.methodList}>
          {availableMethods.length === 0 ? (
            <Text style={styles.emptyMethods}>No hay cuentas habilitadas para pagar en este momento.</Text>
          ) : (
            availableMethods.map((method) => (
              <TouchableOpacity
                key={method}
                style={[styles.methodButton, selectedMethod === method && styles.methodButtonSelected]}
                onPress={() => setSelectedMethod(method)}
              >
                <Text style={[styles.methodButtonText, selectedMethod === method && styles.methodButtonTextSelected]}>{method}</Text>
              </TouchableOpacity>
            ))
          )}
        </View>

        {selectedMethod && methodGroups[selectedMethod]?.length > 0 && (
          <View style={styles.accountList}>
            <Text style={styles.sectionTitle}>Cuentas disponibles</Text>
            {methodGroups[selectedMethod].map((cuenta) => (
              <TouchableOpacity
                key={cuenta.id_cuenta}
                style={[styles.accountCard, selectedCuenta?.id_cuenta === cuenta.id_cuenta && styles.accountCardSelected]}
                onPress={() => setSelectedCuenta(cuenta)}
              >
                <View style={styles.accountHeader}>
                  <Ionicons name="card-outline" size={18} color={COLORS.primary} />
                  <Text style={styles.accountName}>{cuenta.nombre_banco}</Text>
                </View>

                {cuenta.numero_cuenta ? <Text style={styles.accountInfo}>Cuenta: {cuenta.numero_cuenta}</Text> : null}
                {cuenta.telefono_cuenta ? <Text style={styles.accountInfo}>Teléfono: {cuenta.telefono_cuenta}</Text> : null}
                {cuenta.correo_cuenta ? <Text style={styles.accountInfo}>Correo: {cuenta.correo_cuenta}</Text> : null}
                {cuenta.rif_cuenta ? <Text style={styles.accountInfo}>RIF: {cuenta.rif_cuenta}</Text> : null}
              </TouchableOpacity>
            ))}
          </View>
        )}

        {selectedCuenta && (
          <View style={styles.paymentSummaryCard}>
            <Text style={styles.sectionTitle}>Resumen para pagar</Text>
            <Text style={styles.summaryText}>Método: {selectedMethod}</Text>
            <Text style={styles.summaryText}>Banco: {selectedCuenta.nombre_banco}</Text>
            {selectedCuenta.numero_cuenta ? <Text style={styles.summaryText}>Número: {selectedCuenta.numero_cuenta}</Text> : null}
            {selectedCuenta.telefono_cuenta ? <Text style={styles.summaryText}>Teléfono: {selectedCuenta.telefono_cuenta}</Text> : null}
            {selectedCuenta.correo_cuenta ? <Text style={styles.summaryText}>Correo: {selectedCuenta.correo_cuenta}</Text> : null}
            <Text style={styles.summaryText}>Monto a pagar: {formatCurrency(pedido?.total || pedido?.monto || 0)}</Text>

            <TouchableOpacity style={styles.confirmButton} onPress={handleConfirmPayment}>
              <Text style={styles.confirmButtonText}>Confirmar Pago</Text>
            </TouchableOpacity>
          </View>
        )}
      </ScrollView>

      <Modal visible={showPaymentForm} transparent animationType="slide" onRequestClose={() => setShowPaymentForm(false)}>
        <View style={styles.modalOverlay}>
          <View style={styles.paymentFormContainer}>
            <View style={styles.formHeader}>
              <Text style={styles.formTitle}>Registrar {selectedMethod}</Text>
              <TouchableOpacity onPress={() => setShowPaymentForm(false)}>
                <Ionicons name="close" size={22} color={COLORS.text} />
              </TouchableOpacity>
            </View>

            <ScrollView contentContainerStyle={styles.formContent} showsVerticalScrollIndicator={false}>
              {paymentFormFields.map((field) => (
                <View key={field.key} style={styles.formField}>
                  <Text style={styles.formLabel}>{field.label}</Text>
                  {field.key === 'banco_origen' ? (
                    <TouchableOpacity
                      style={[styles.formInput, styles.selectInput, validationErrors.length > 0 && styles.formInputError]}
                      onPress={() => setBankSelectorVisible(true)}
                    >
                      <Text style={paymentForm.banco_origen ? styles.selectValue : styles.selectPlaceholder}>
                        {paymentForm.banco_origen || 'Seleccione un banco'}
                      </Text>
                      <Ionicons name="chevron-down" size={18} color={COLORS.textSecondary} />
                    </TouchableOpacity>
                  ) : (
                    <TextInput
                      style={[styles.formInput, validationErrors.length > 0 && styles.formInputError]}
                      value={paymentForm[field.key]}
                      onChangeText={(value) => {
                        let filteredValue = limpiarCampoPago(field.key, value);

                      if (field.key === 'telefono') {
                        filteredValue = formatPhoneNumber(value);
                      }

                      if (field.key === 'monto') {
                        const totalPedido = Number(pedido?.total || pedido?.monto || 0);
                        const montoIngresado = parsearNumeroFormateado(filteredValue);

                        if (montoIngresado > totalPedido) {
                          const montoMaximo = Math.floor(totalPedido * 100) / 100;
                          filteredValue = montoMaximo.toFixed(2);
                        }
                      }

                      setPaymentForm((previous) => ({ ...previous, [field.key]: filteredValue }));
                      setValidationErrors([]);
                      }}
                      placeholder={field.placeholder}
                      keyboardType={field.keyboardType || 'default'}
                      maxLength={field.maxLength || 200}
                    />
                  )}
                </View>
              ))}

              {validationErrors.length > 0 ? (
                <View style={styles.validationBox}>
                  <Text style={styles.validationTitle}>Revisa los datos ingresados:</Text>
                  {validationErrors.map((error, index) => (
                    <Text key={`${error}-${index}`} style={styles.validationText}>{error}</Text>
                  ))}
                </View>
              ) : null}

              <TouchableOpacity
                style={[styles.uploadButton, validationErrors.length > 0 && styles.uploadButtonError]}
                onPress={() => {
                  setValidationErrors([]);
                  handlePickComprobante();
                }}
              >
                <Ionicons name="image-outline" size={18} color={COLORS.primary} />
                <Text style={styles.uploadButtonText}>
                  {paymentForm.comprobanteName ? paymentForm.comprobanteName : 'Adjuntar comprobante'}
                </Text>
              </TouchableOpacity>

              {paymentForm.comprobante ? (
                <Image source={{ uri: paymentForm.comprobante }} style={styles.comprobantePreview} resizeMode="cover" />
              ) : null}

              <TouchableOpacity style={styles.submitButton} onPress={handleSubmitPaymentForm} disabled={isSubmitting}>
                <Text style={styles.submitButtonText}>{isSubmitting ? 'Enviando...' : 'Enviar Pago'}</Text>
              </TouchableOpacity>
            </ScrollView>
          </View>
        </View>
      </Modal>

      <Modal
        visible={bankSelectorVisible}
        transparent
        animationType="fade"
        onRequestClose={() => setBankSelectorVisible(false)}
      >
        <View style={styles.bankSelectorOverlay}>
          <View style={styles.bankSelectorCard}>
            <View style={styles.formHeader}>
              <Text style={styles.formTitle}>Seleccione el banco de origen</Text>
              <TouchableOpacity onPress={() => setBankSelectorVisible(false)}>
                <Ionicons name="close" size={22} color={COLORS.text} />
              </TouchableOpacity>
            </View>
            <ScrollView contentContainerStyle={styles.bankList}>
              {BANCOS_VENEZUELA.map((banco) => (
                <TouchableOpacity
                  key={banco}
                  style={styles.bankOption}
                  onPress={() => {
                    setPaymentForm((previous) => ({ ...previous, banco_origen: banco }));
                    setValidationErrors([]);
                    setBankSelectorVisible(false);
                  }}
                >
                  <Text style={styles.bankOptionText}>{banco}</Text>
                  {paymentForm.banco_origen === banco ? (
                    <Ionicons name="checkmark" size={20} color={COLORS.primary} />
                  ) : null}
                </TouchableOpacity>
              ))}
            </ScrollView>
          </View>
        </View>
      </Modal>

      <Modal
        animationType="fade"
        transparent={true}
        visible={paymentSuccessVisible}
        onRequestClose={() => {
          setPaymentSuccessVisible(false);
          navigateToPedidos();
        }}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.successModalCard}>
            <Ionicons name="checkmark-circle" size={52} color={COLORS.success || '#22c55e'} />
            <Text style={styles.successTitle}>Pago registrado</Text>
            <Text style={styles.successText}>{paymentSuccessMessage}</Text>

            <TouchableOpacity
              style={styles.successPrimaryButton}
              onPress={() => {
                setPaymentSuccessVisible(false);
                navigateToPedidos();
              }}
            >
              <Text style={styles.successPrimaryText}>Ver pedidos</Text>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F5F7FA',
  },
  centered: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F5F7FA',
  },
  content: {
    padding: 16,
    paddingBottom: 32,
  },
  loadingText: {
    marginTop: 12,
    color: COLORS.textSecondary,
    fontSize: 14,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.text,
    marginBottom: 12,
  },
  orderCard: {
    backgroundColor: '#fff',
    borderRadius: 14,
    padding: 16,
    marginBottom: 18,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.08,
    shadowRadius: 6,
    elevation: 3,
  },
  orderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 8,
  },
  orderLabel: {
    color: COLORS.textSecondary,
    fontSize: 13,
  },
  orderValue: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '700',
    textAlign: 'right',
  },
  methodList: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    marginBottom: 18,
  },
  methodButton: {
    backgroundColor: '#E5E7EB',
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 10,
    marginBottom: 8,
  },
  methodButtonSelected: {
    backgroundColor: COLORS.primary,
  },
  methodButtonText: {
    color: COLORS.text,
    fontWeight: '700',
    fontSize: 13,
  },
  methodButtonTextSelected: {
    color: '#fff',
  },
  emptyMethods: {
    color: COLORS.textSecondary,
    fontSize: 14,
  },
  accountList: {
    marginBottom: 18,
  },
  accountCard: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  accountCardSelected: {
    borderColor: COLORS.primary,
    backgroundColor: '#F5F9FF',
  },
  accountHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  accountName: {
    marginLeft: 8,
    fontWeight: '700',
    color: COLORS.text,
    fontSize: 15,
  },
  accountInfo: {
    color: COLORS.textSecondary,
    fontSize: 13,
    marginBottom: 4,
  },
  paymentSummaryCard: {
    backgroundColor: '#fff',
    borderRadius: 14,
    padding: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.08,
    shadowRadius: 6,
    elevation: 3,
  },
  summaryText: {
    color: COLORS.text,
    fontSize: 14,
    marginBottom: 6,
  },
  confirmButton: {
    marginTop: 16,
    backgroundColor: COLORS.primary,
    borderRadius: 10,
    paddingVertical: 12,
    alignItems: 'center',
  },
  confirmButtonText: {
    color: '#fff',
    fontWeight: '700',
    fontSize: 15,
  },
  successModalCard: {
    backgroundColor: '#fff',
    borderRadius: 18,
    padding: 22,
    marginHorizontal: 24,
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.15,
    shadowRadius: 10,
    elevation: 8,
  },
  successTitle: {
    marginTop: 12,
    fontSize: 20,
    fontWeight: '800',
    color: COLORS.text,
  },
  successText: {
    marginTop: 10,
    color: COLORS.textSecondary,
    fontSize: 14,
    textAlign: 'center',
    lineHeight: 22,
  },
  successPrimaryButton: {
    marginTop: 18,
    backgroundColor: COLORS.primary,
    borderRadius: 10,
    paddingVertical: 12,
    paddingHorizontal: 18,
    width: '100%',
    alignItems: 'center',
  },
  successPrimaryText: {
    color: '#fff',
    fontWeight: '700',
    fontSize: 14,
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.5)',
    justifyContent: 'flex-end',
  },
  paymentFormContainer: {
    backgroundColor: '#fff',
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    maxHeight: '90%',
    paddingBottom: 20,
  },
  formHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 18,
    paddingVertical: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
  },
  formTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.text,
  },
  formContent: {
    paddingHorizontal: 18,
    paddingTop: 16,
    paddingBottom: 8,
  },
  formField: {
    marginBottom: 16,
  },
  formLabel: {
    fontSize: 13,
    fontWeight: '600',
    color: COLORS.textSecondary,
    marginBottom: 8,
  },
  formInput: {
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 10,
    backgroundColor: '#F9FAFB',
    paddingHorizontal: 12,
    paddingVertical: 12,
    color: COLORS.text,
    fontSize: 14,
  },
  formInputError: {
    borderColor: '#DC2626',
  },
  selectInput: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  selectValue: {
    color: COLORS.text,
    fontSize: 14,
    flex: 1,
  },
  selectPlaceholder: {
    color: '#9CA3AF',
    fontSize: 14,
    flex: 1,
  },
  validationBox: {
    borderWidth: 1,
    borderColor: '#FCA5A5',
    borderRadius: 10,
    backgroundColor: '#FEF2F2',
    padding: 12,
    marginBottom: 14,
  },
  validationTitle: {
    color: '#991B1B',
    fontWeight: '700',
    marginBottom: 4,
  },
  validationText: {
    color: '#B91C1C',
    fontSize: 13,
    lineHeight: 20,
  },
  bankSelectorOverlay: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.45)',
    justifyContent: 'center',
    padding: 20,
  },
  bankSelectorCard: {
    backgroundColor: '#fff',
    borderRadius: 16,
    maxHeight: '85%',
    overflow: 'hidden',
  },
  bankList: {
    paddingHorizontal: 18,
    paddingBottom: 12,
  },
  bankOption: {
    minHeight: 48,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
    paddingVertical: 10,
  },
  bankOptionText: {
    color: COLORS.text,
    fontSize: 14,
    flex: 1,
    paddingRight: 12,
  },
  uploadButton: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: COLORS.primary,
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 12,
    marginBottom: 14,
    backgroundColor: '#EEF4FF',
  },
  uploadButtonError: {
    borderColor: '#DC2626',
  },
  uploadButtonText: {
    marginLeft: 8,
    color: COLORS.primary,
    fontWeight: '600',
    flexShrink: 1,
  },
  comprobantePreview: {
    width: '100%',
    height: 180,
    borderRadius: 12,
    marginBottom: 16,
    backgroundColor: '#F3F4F6',
  },
  submitButton: {
    backgroundColor: COLORS.primary,
    borderRadius: 10,
    paddingVertical: 14,
    alignItems: 'center',
  },
  submitButtonText: {
    color: '#fff',
    fontWeight: '700',
    fontSize: 15,
  },
});

export default PaymentScreen;