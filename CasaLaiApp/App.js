import 'react-native-gesture-handler';
import React, { useEffect } from 'react';
import { View, ActivityIndicator } from 'react-native';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { Provider, useDispatch, useSelector } from 'react-redux';
import { PersistGate } from 'redux-persist/integration/react';
import { NavigationContainer } from '@react-navigation/native';
import { store, persistor } from './src/redux/store';
import { CartProvider } from './src/context/CartContext';
import AppNavigator from './src/navigation/AppNavigator';
// import TestApiScreen from './src/components/TestApiScreen';
import { checkAuth, refreshAuthSession } from './src/redux/slices/authSlice';
import COLORS from './src/constants/colors';

const AppContent = () => {
  const dispatch = useDispatch();
  const { appReady } = useSelector((state) => state.auth);

  useEffect(() => {
    const bootstrapAuth = async () => {
      await dispatch(checkAuth());
      await dispatch(refreshAuthSession());
    };

    bootstrapAuth();
  }, [dispatch]);

  if (!appReady) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator size="large" color={COLORS.primary} />
      </View>
    );
  }

  return (
    <CartProvider>
      <AppNavigator />
    </CartProvider>
  );
  //return <TestApiScreen />;
};

const App = () => {
  return (
    <Provider store={store}>
      <PersistGate loading={null} persistor={persistor}>
        <SafeAreaProvider>
          <StatusBar style="light" backgroundColor={COLORS.primary} />
          <NavigationContainer>
            <AppContent />
          </NavigationContainer>
        </SafeAreaProvider>
      </PersistGate>
    </Provider>
  );
};

export default App;