import React from 'react';
import { createStackNavigator } from '@react-navigation/stack';
import { SafeAreaView } from 'react-native-safe-area-context';
import { TouchableOpacity } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import HomeScreen from '../screens/main/HomeScreen';
import AmazonStyleHomeScreen from '../screens/main/AmazonStyleHomeScreen';
import ProductDetailScreen from '../screens/product/ProductDetailScreen';
import PaymentScreen from '../screens/main/PaymentScreen';
import COLORS from '../constants/colors';
import ProfileScreen from '../screens/main/ProfileScreen';
import CatalogScreen from '../screens/main/CatalogScreen';
import ProductsScreen from '../screens/main/ProductsScreen';
import ReceptionScreen from '../screens/main/ReceptionScreen';
import IncomeExpenseScreen from '../screens/main/IncomeExpenseScreen';
import UserManualScreen from '../screens/main/UserManualScreen';
import SettingsScreen from '../screens/main/SettingsScreen';
import HelpScreen from '../screens/main/HelpScreen';
import { View } from 'react-native';

const Stack = createStackNavigator();

// Componente para envolver las pantallas con SafeAreaView
const SafeAreaWrapper = ({ children }) => (
  <View style={{ flex: 1 }}>
    <SafeAreaView
      style={{ flex: 1 }}
      edges={['right', 'left', 'top']}
    >
      {children}
    </SafeAreaView>
  </View>
);

// Botón de retroceder personalizado que navega a la pestaña Menu
const BackToMenuButton = ({ navigation }) => (
  <TouchableOpacity
    onPress={() => {
      // Navegar a la pestaña Menu del BottomTabNavigator
      navigation.getParent()?.navigate('Menu');
    }}
    style={{ marginLeft: 10 }}
  >
    <Ionicons name="arrow-back" size={24} color={COLORS.white} />
  </TouchableOpacity>
);

const HomeStack = () => {
  return (
    <Stack.Navigator
      screenOptions={{
        headerShown: false,
        animationEnabled: false,
      }}
    >
      <Stack.Screen name="Home">
        {() => (
          <SafeAreaWrapper>
            <AmazonStyleHomeScreen />
          </SafeAreaWrapper>
        )}
      </Stack.Screen>
      <Stack.Screen 
        name="ProductDetail" 
        component={ProductDetailScreen} 
        options={{
          headerShown: true,
          title: 'Detalles del Producto',
          headerBackTitle: 'Atrás',
          headerStyle: {
            backgroundColor: '#fff',
            elevation: 0,
            shadowOpacity: 0,
            borderBottomWidth: 0,
          },
          headerTintColor: '#000',
        }}
      />
      <Stack.Screen 
        name="Payment" 
        component={PaymentScreen}
        options={{
          headerShown: true,
          title: 'Pago',
          headerBackTitle: 'Atrás',
        }}
      />
      <Stack.Screen
        name="Profile"
        component={ProfileScreen}
        options={{
          headerShown: true,
          title: 'Mi Perfil',
          headerBackTitle: 'Atrás',
          headerStyle: {
            backgroundColor: COLORS.primary,
            elevation: 0,
            shadowOpacity: 0,
            borderBottomWidth: 0,
          },
          headerTintColor: COLORS.white,
          headerTitleStyle: {
            fontWeight: 'bold',
          },
        }}
      />
      <Stack.Screen
        name="Catalog"
        component={CatalogScreen}
        options={({ navigation }) => ({
          headerShown: true,
          title: 'Catálogo',
          headerBackTitle: 'Atrás',
          headerStyle: {
            backgroundColor: COLORS.primary,
            elevation: 0,
            shadowOpacity: 0,
            borderBottomWidth: 0,
          },
          headerTintColor: COLORS.white,
          headerTitleStyle: {
            fontWeight: 'bold',
          },
          headerLeft: () => <BackToMenuButton navigation={navigation} />,
          gestureEnabled: false,
        })}
      />
      <Stack.Screen
        name="Products"
        component={ProductsScreen}
        options={{
          headerShown: true,
          title: 'Productos',
          headerBackTitle: 'Atrás',
          headerStyle: {
            backgroundColor: COLORS.primary,
            elevation: 0,
            shadowOpacity: 0,
            borderBottomWidth: 0,
          },
          headerTintColor: COLORS.white,
          headerTitleStyle: {
            fontWeight: 'bold',
          },
        }}
      />
      <Stack.Screen
        name="Reception"
        component={ReceptionScreen}
        options={{
          headerShown: true,
          title: 'Recepción',
          headerBackTitle: 'Atrás',
          headerStyle: {
            backgroundColor: COLORS.primary,
            elevation: 0,
            shadowOpacity: 0,
            borderBottomWidth: 0,
          },
          headerTintColor: COLORS.white,
          headerTitleStyle: {
            fontWeight: 'bold',
          },
        }}
      />
      <Stack.Screen
        name="IncomeExpense"
        component={IncomeExpenseScreen}
        options={{
          headerShown: true,
          title: 'Ingresos y Egresos',
          headerBackTitle: 'Atrás',
          headerStyle: {
            backgroundColor: COLORS.primary,
            elevation: 0,
            shadowOpacity: 0,
            borderBottomWidth: 0,
          },
          headerTintColor: COLORS.white,
          headerTitleStyle: {
            fontWeight: 'bold',
          },
        }}
      />
      <Stack.Screen
        name="UserManual"
        component={UserManualScreen}
        options={({ navigation }) => ({
          headerShown: true,
          title: 'Manual de Usuario',
          headerBackTitle: 'Atrás',
          headerStyle: {
            backgroundColor: COLORS.primary,
            elevation: 0,
            shadowOpacity: 0,
            borderBottomWidth: 0,
          },
          headerTintColor: COLORS.white,
          headerTitleStyle: {
            fontWeight: 'bold',
          },
          headerLeft: () => <BackToMenuButton navigation={navigation} />,
          gestureEnabled: false,
        })}
      />
      <Stack.Screen
        name="Settings"
        component={SettingsScreen}
        options={({ navigation }) => ({
          headerShown: true,
          title: 'Configuración',
          headerBackTitle: 'Atrás',
          headerStyle: {
            backgroundColor: COLORS.primary,
            elevation: 0,
            shadowOpacity: 0,
            borderBottomWidth: 0,
          },
          headerTintColor: COLORS.white,
          headerTitleStyle: {
            fontWeight: 'bold',
          },
          headerLeft: () => <BackToMenuButton navigation={navigation} />,
          gestureEnabled: false,
        })}
      />
      <Stack.Screen
        name="Help"
        component={HelpScreen}
        options={({ navigation }) => ({
          headerShown: true,
          title: 'Ayuda',
          headerBackTitle: 'Atrás',
          headerStyle: {
            backgroundColor: COLORS.primary,
            elevation: 0,
            shadowOpacity: 0,
            borderBottomWidth: 0,
          },
          headerTintColor: COLORS.white,
          headerTitleStyle: {
            fontWeight: 'bold',
          },
          headerLeft: () => <BackToMenuButton navigation={navigation} />,
          gestureEnabled: false,
        })}
      />
    </Stack.Navigator>
  );
};

export default HomeStack;
