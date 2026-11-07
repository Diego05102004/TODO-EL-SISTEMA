import React from 'react';
import { createDrawerNavigator, DrawerContentScrollView, DrawerItemList } from '@react-navigation/drawer';
import { Ionicons } from '@expo/vector-icons';
import { View } from 'react-native';
import COLORS from '../constants/colors';
import BottomTabNavigator from './BottomTabNavigator';
import ProfileScreen from '../screens/main/ProfileScreen';
import CartScreen from '../screens/main/CartScreen';
import CatalogScreen from '../screens/main/CatalogScreen';
import ReceptionScreen from '../screens/main/ReceptionScreen';
import IncomeExpenseScreen from '../screens/main/IncomeExpenseScreen';
import ProductsScreen from '../screens/main/ProductsScreen';

const Drawer = createDrawerNavigator();

const CustomDrawerContent = (props) => {
  return (
    <DrawerContentScrollView
      {...props}
      contentContainerStyle={{
        paddingTop: 20,
        flex: 1,
      }}
    >
      <DrawerItemList {...props} />
    </DrawerContentScrollView>
  );
};

const DrawerNavigator = () => {
  return (
    <Drawer.Navigator
      drawerContent={(props) => <CustomDrawerContent {...props} />}
      screenOptions={{
        headerShown: false,
        drawerType: 'slide',
        drawerActiveTintColor: COLORS.primary,
        drawerInactiveTintColor: 'gray',
        drawerLabelStyle: {
          fontSize: 16,
          marginLeft: -10,
        },
        drawerItemStyle: {
          marginVertical: 5,
        },
        overlayColor: 'transparent',
        swipeEnabled: true,
      }}
    >
      <Drawer.Screen
        name="Inicio"
        component={BottomTabNavigator}
        options={{
          drawerIcon: ({ color, size }) => (
            <Ionicons name="home-outline" size={size} color={color} />
          ),
        }}
      />
      <Drawer.Screen
        name="Perfil"
        component={ProfileScreen}
        options={{
          drawerIcon: ({ color, size }) => (
            <Ionicons name="person" size={size} color={color} />
          ),
        }}
      />
      <Drawer.Screen
        name="Carrito"
        component={CartScreen}
        options={{
          drawerIcon: ({ color, size }) => (
            <Ionicons name="cart" size={size} color={color} />
          ),
        }}
      />
      <Drawer.Screen
        name="Catálogo"
        component={CatalogScreen}
        options={{
          drawerIcon: ({ color, size }) => (
            <Ionicons name="list" size={size} color={color} />
          ),
        }}
      />
      <Drawer.Screen
        name="Recepción"
        component={ReceptionScreen}
        options={{
          drawerIcon: ({ color, size }) => (
            <Ionicons name="archive" size={size} color={color} />
          ),
        }}
      />
      <Drawer.Screen
        name="Ingreso y Egreso"
        component={IncomeExpenseScreen}
        options={{
          drawerIcon: ({ color, size }) => (
            <Ionicons name="cash" size={size} color={color} />
          ),
        }}
      />
      <Drawer.Screen
        name="Productos"
        component={ProductsScreen}
        options={{
          drawerIcon: ({ color, size }) => (
            <Ionicons name="pricetags" size={size} color={color} />
          ),
        }}
      />
    </Drawer.Navigator>
  );
};

export default DrawerNavigator;
