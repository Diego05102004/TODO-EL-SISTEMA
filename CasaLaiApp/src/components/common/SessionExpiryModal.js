import React, { useEffect, useRef } from 'react';
import { Animated, Modal, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import COLORS from '../../constants/colors';

const SessionExpiryModal = ({
  visible,
  secondsLeft,
  isExtending,
  onExtend,
  onClose,
  remainingExtensions = 0,
}) => {
  const scaleAnim = useRef(new Animated.Value(0.9)).current;
  const opacityAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    if (visible) {
      Animated.parallel([
        Animated.timing(scaleAnim, {
          toValue: 1,
          duration: 220,
          useNativeDriver: true,
        }),
        Animated.timing(opacityAnim, {
          toValue: 1,
          duration: 220,
          useNativeDriver: true,
        }),
      ]).start();
    } else {
      scaleAnim.setValue(0.9);
      opacityAnim.setValue(0);
    }
  }, [visible, scaleAnim, opacityAnim]);

  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
      statusBarTranslucent
    >
      <View style={styles.overlay}>
        <Animated.View
          style={[styles.card, { transform: [{ scale: scaleAnim }], opacity: opacityAnim }]}
        >
          <View style={styles.progressCircleContainer}>
            <View style={styles.progressCircleOuter}>
              <View style={styles.progressCircleInner}>
                <Text style={styles.progressText}>{secondsLeft ?? 0}</Text>
              </View>
            </View>
          </View>

          <Text style={styles.title}>Tu sesión está por expirar</Text>
          <Text style={styles.message}>
            Quedan {secondsLeft ?? 0} segundos antes de que tu sesión expire.
          </Text>
          <Text style={styles.subtitle}>
            Extensiones disponibles: {remainingExtensions} de 3.
          </Text>
          <Text style={styles.subtitle}>
            Puedes extenderla para continuar usando la app sin perder tu sesión.
          </Text>

          <View style={styles.actions}>
            <TouchableOpacity
              style={[styles.button, styles.primaryButton]}
              onPress={onExtend}
              disabled={isExtending}
            >
              <Text style={styles.buttonText}>
                {isExtending ? 'Extendiendo...' : 'Extender sesión'}
              </Text>
            </TouchableOpacity>

            <TouchableOpacity style={[styles.button, styles.secondaryButton]} onPress={onClose}>
              <Text style={styles.buttonText}>Cerrar sesión</Text>
            </TouchableOpacity>
          </View>
        </Animated.View>
      </View>
    </Modal>
  );
};

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.65)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
  },
  card: {
    width: '100%',
    maxWidth: 420,
    backgroundColor: '#fff',
    borderRadius: 16,
    padding: 24,
    alignItems: 'center',
  },
  progressCircleContainer: {
    marginBottom: 12,
  },
  progressCircleOuter: {
    width: 92,
    height: 92,
    borderRadius: 46,
    borderWidth: 8,
    borderColor: '#f0c36d',
    justifyContent: 'center',
    alignItems: 'center',
  },
  progressCircleInner: {
    width: 70,
    height: 70,
    borderRadius: 35,
    backgroundColor: '#fff5dc',
    justifyContent: 'center',
    alignItems: 'center',
  },
  progressText: {
    fontSize: 20,
    fontWeight: '700',
    color: COLORS.primary,
  },
  title: {
    fontSize: 22,
    fontWeight: '700',
    color: COLORS.text || '#222',
    marginBottom: 10,
    textAlign: 'center',
  },
  message: {
    fontSize: 16,
    color: '#444',
    textAlign: 'center',
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 14,
    color: '#666',
    textAlign: 'center',
    marginBottom: 20,
  },
  actions: {
    width: '100%',
    gap: 10,
  },
  button: {
    width: '100%',
    paddingVertical: 12,
    borderRadius: 10,
    alignItems: 'center',
    marginBottom: 10,
  },
  primaryButton: {
    backgroundColor: COLORS.primary,
  },
  secondaryButton: {
    backgroundColor: '#e53935',
  },
  buttonText: {
    color: '#fff',
    fontWeight: '600',
    fontSize: 15,
  },
});

export default SessionExpiryModal;
