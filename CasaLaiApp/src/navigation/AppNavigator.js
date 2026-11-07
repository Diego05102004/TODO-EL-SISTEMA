import React, { useEffect, useState } from 'react';
import { createStackNavigator } from '@react-navigation/stack';
import { useDispatch, useSelector } from 'react-redux';
import { ActivityIndicator, Alert, View } from 'react-native';
import COLORS from '../constants/colors';
import SessionExpiryModal from '../components/common/SessionExpiryModal';
import { checkAuth, extendSessionCounter, logoutUser, refreshAuthSession } from '../redux/slices/authSlice';
import { extendStoredSession } from '../utils/jwt';

// Screens
import LoginScreen from '../screens/auth/LoginScreen';
import RegisterScreen from '../screens/auth/RegisterScreen';
import HelpScreen from '../screens/main/HelpScreen';

// Navigators - usar solo Bottom Tabs
import BottomTabNavigator from './BottomTabNavigator';

const Stack = createStackNavigator();

const AppNavigator = () => {
  const dispatch = useDispatch();
  const { isAuthenticated, appReady, tokenStatus, expiresIn, sessionExtensions = 0 } = useSelector((state) => state.auth);
  const [showSessionModal, setShowSessionModal] = useState(false);

  useEffect(() => {
    if (!isAuthenticated) {
      setShowSessionModal(false);
      return;
    }

    console.log('[JWT] Iniciando validación periódica del token');

    const intervalId = setInterval(() => {
      dispatch(checkAuth())
        .unwrap()
        .then((result) => {
          if (result?.debug) {
            console.log('[JWT][debug] resultado de validación', result.debug);
          }

          if (result?.tokenStatus === 'warning') {
            console.log('[JWT] Sesión próxima a expirar:', result.expiresIn, 'segundos');
          } else if (result?.tokenStatus === 'expired' || result?.tokenStatus === 'invalid') {
            console.log('[JWT] Sesión expirada o inválida; cerrando sesión');
            Alert.alert('Sesión expirada', 'Tu sesión ha expirado. Inicia sesión nuevamente para continuar.');
            dispatch(logoutUser());
          }
        })
        .catch((error) => {
          console.warn('[JWT] Error al validar sesión en tiempo real:', error);
        });
    }, 1000);

    return () => clearInterval(intervalId);
  }, [dispatch, isAuthenticated]);

  useEffect(() => {
    if (isAuthenticated && tokenStatus === 'warning') {
      setShowSessionModal(true);
    } else {
      setShowSessionModal(false);
    }
  }, [isAuthenticated, tokenStatus]);

  const handleExtendSession = async () => {
    try {
      if (sessionExtensions >= 3) {
        Alert.alert('Límite alcanzado', 'Ya utilizaste las 3 extensiones permitidas para esta sesión.');
        return;
      }

      await extendStoredSession(60);
      dispatch(extendSessionCounter());
      await dispatch(refreshAuthSession());
      setShowSessionModal(false);
      Alert.alert('Sesión extendida', 'Tu sesión fue extendida por 1 minuto.');
    } catch (error) {
      console.warn('[JWT] No se pudo extender la sesión:', error);
    }
  };

  const handleCloseSession = async () => {
    await dispatch(logoutUser());
    setShowSessionModal(false);
  };

  if (!appReady) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator size="large" color={COLORS.primary} />
      </View>
    );
  }

  return (
    <>
      <Stack.Navigator
        screenOptions={{
          headerShown: false,
          animationEnabled: false,
        }}
      >
        {isAuthenticated ? (
          <>
            <Stack.Screen name="Main" component={BottomTabNavigator} />
            <Stack.Screen name="Help" component={HelpScreen} />
          </>
        ) : (
          <>
            <Stack.Screen name="Login" component={LoginScreen} />
            <Stack.Screen name="Register" component={RegisterScreen} />
          </>
        )}
      </Stack.Navigator>

      <SessionExpiryModal
        visible={showSessionModal && isAuthenticated}
        secondsLeft={expiresIn ?? 0}
        isExtending={false}
        onExtend={handleExtendSession}
        onClose={handleCloseSession}
        remainingExtensions={Math.max(0, 3 - sessionExtensions)}
      />
    </>
  );
};

export default AppNavigator;