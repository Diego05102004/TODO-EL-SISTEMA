import React, { useState } from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';
import { View, Text, StyleSheet } from 'react-native';
import { useCart } from '../hooks/useCart';
import HomeStack from './HomeStack';
import CartScreen from '../screens/main/CartScreen';
import PedidosScreen from '../screens/main/PedidosScreen';
import ProfileScreen from '../screens/main/ProfileScreen';
import MenuScreen from '../screens/main/MenuScreen';
import COLORS from '../constants/colors';
import theme from '../constants/theme';

// Desactivar animaciones para no depender de Reanimated
const tabBarOptions = {
  keyboardHidesTabBar: true,
  tabBarHideOnKeyboard: true,
  tabBarActiveTintColor: COLORS.primary,
  tabBarInactiveTintColor: 'gray',
  headerShown: false,
  tabBarShowLabel: false,
  tabBarStyle: {
    display: 'flex',
    borderTopWidth: 1,
    borderTopColor: '#e0e0e0',
    backgroundColor: '#fff',
    height: 60,
    paddingBottom: 5,
    paddingTop: 5,
  },
};

const Tab = createBottomTabNavigator();

const CartIconWithBadge = ({ focused, color, size }) => {
  const { getItemCount } = useCart();
  const itemCount = getItemCount();

  return (
    <View>
      <Ionicons
        name={focused ? 'cart' : 'cart-outline'}
        size={size}
        color={color}
      />
      {itemCount > 0 && (
        <View style={[
          styles.badge,
          {
            backgroundColor: COLORS.danger,
            borderColor: focused ? COLORS.background : COLORS.white
          }
        ]}>
          <Text style={styles.badgeText}>
            {itemCount > 9 ? '9+' : itemCount}
          </Text>
        </View>
      )}
    </View>
  );
};

const BottomTabNavigator = () => {
  const [menuVisible, setMenuVisible] = useState(false);

  return (
    <>
      <Tab.Navigator
        screenOptions={({ route, navigation }) => ({
          ...tabBarOptions,
          tabBarIcon: ({ focused, color }) => {
            const iconSize = 28;
            if (route.name === 'Inicio') {
              return (
                <Ionicons
                  name={focused ? 'home' : 'home-outline'}
                  size={iconSize}
                  color={color}
                />
              );
            } else if (route.name === 'Pedidos') {
              return (
                <Ionicons
                  name={focused ? 'receipt' : 'receipt-outline'}
                  size={iconSize}
                  color={color}
                />
              );
            } else if (route.name === 'Carrito') {
              return <CartIconWithBadge focused={focused} color={color} size={iconSize} />;
            } else if (route.name === 'Perfil') {
              return (
                <Ionicons
                  name={focused ? 'person' : 'person-outline'}
                  size={iconSize}
                  color={color}
                />
              );
            } else if (route.name === 'Menu') {
              return (
                <Ionicons
                  name={focused ? 'menu' : 'menu-outline'}
                  size={iconSize}
                  color={color}
                />
              );
            }
          },
        })}
      >
        <Tab.Screen name="Inicio" component={HomeStack} />
        <Tab.Screen name="Pedidos" component={PedidosScreen} />
        <Tab.Screen name="Carrito" component={CartScreen} />
        <Tab.Screen name="Perfil" component={ProfileScreen} />
        <Tab.Screen name="Menu" component={MenuScreen} />
      </Tab.Navigator>
    </>
  );
};

const styles = StyleSheet.create({
  badge: {
    position: 'absolute',
    right: -8,
    top: -4,
    borderRadius: 10,
    width: 20,
    height: 20,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 2,
  },
  badgeText: {
    color: COLORS.white,
    fontSize: 10,
    fontWeight: 'bold',
  },
});

export default BottomTabNavigator;
