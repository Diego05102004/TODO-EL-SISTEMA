import React, { useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ScrollView, Platform } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useDispatch } from 'react-redux';
import { useNavigation } from '@react-navigation/native';
import { logoutUser } from '../../redux/slices/authSlice';
import COLORS from '../../constants/colors';
import theme from '../../constants/theme';
import { Ionicons } from '@expo/vector-icons';
import ConfirmModal from '../../components/common/ConfirmModal';

const MenuScreen = () => {
  const dispatch = useDispatch();
  const navigation = useNavigation();
  const [showLogoutConfirm, setShowLogoutConfirm] = useState(false);

  const handleLogout = () => {
    setShowLogoutConfirm(true);
  };

  const confirmLogout = () => {
    dispatch(logoutUser());
    setShowLogoutConfirm(false);
  };

  const cancelLogout = () => {
    setShowLogoutConfirm(false);
  };

  const menuItems = [
    { id: '1', title: 'Catálogo', icon: 'grid-outline', screen: 'Catalog' },
    { id: '2', title: 'Pagos', icon: 'card-outline', screen: 'Payment' },
    { id: '3', title: 'Manual de Usuario', icon: 'book-outline', screen: 'UserManual' },
    { id: '4', title: 'Configuración', icon: 'settings-outline', screen: 'Settings' },
  ];

  // Usar SafeAreaView solo si no estamos en web
  const Container = Platform.OS === 'web' ? View : SafeAreaView;
  const containerProps = Platform.OS === 'web' ? {} : { edges: ['top'] };

  return (
    <>
      <Container style={styles.container} {...containerProps}>
        <View style={styles.header}>
          <Text style={styles.title}>Menú</Text>
        </View>
        
        <ScrollView style={styles.menuList}>
          {menuItems.map((item) => (
            <TouchableOpacity
              key={item.id}
              style={styles.menuItem}
              onPress={() => {
                if (item.screen) {
                  navigation.navigate(item.screen);
                }
              }}
            >
              <Ionicons name={item.icon} size={24} color={COLORS.primary} style={styles.menuIcon} />
              <Text style={styles.menuText}>{item.title}</Text>
              <Ionicons name="chevron-forward" size={20} color={COLORS.textSecondary} />
            </TouchableOpacity>
          ))}
        </ScrollView>

        <View style={styles.footer}>
          <TouchableOpacity 
            style={styles.logoutButton}
            onPress={handleLogout}
          >
            <Ionicons name="log-out-outline" size={20} color={COLORS.danger} style={styles.logoutIcon} />
            <Text style={styles.logoutText}>Cerrar Sesión</Text>
          </TouchableOpacity>
        </View>
      </Container>

      <ConfirmModal
        visible={showLogoutConfirm}
        title="Cerrar Sesión"
        message="¿Estás seguro de que deseas cerrar sesión?"
        onConfirm={confirmLogout}
        onCancel={cancelLogout}
        confirmText="Cerrar Sesión"
        cancelText="Cancelar"
      />
    </>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  header: {
    backgroundColor: COLORS.primary,
    padding: theme.spacing.lg,
    borderBottomLeftRadius: 20,
    borderBottomRightRadius: 20,
    marginBottom: 10,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
    alignItems: 'center',
  },
  title: {
    ...theme.typography.h2,
    color: COLORS.white,
    textAlign: 'center',
    fontWeight: 'bold',
  },
  menuList: {
    flex: 1,
    padding: theme.spacing.md,
  },
  menuItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: theme.spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.lightGray,
  },
  menuIcon: {
    marginRight: theme.spacing.md,
    width: 24,
    textAlign: 'center',
  },
  menuText: {
    ...theme.typography.body,
    flex: 1,
    color: COLORS.text,
  },
  footer: {
    padding: theme.spacing.lg,
    borderTopWidth: 1,
    borderTopColor: COLORS.lightGray,
    marginBottom: theme.spacing.lg,
    elevation: 10,
    zIndex: 10,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: -2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    backgroundColor: COLORS.background,
  },
  logoutButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(220, 53, 69, 0.1)',
    borderRadius: 8,
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderWidth: 1,
    borderColor: 'rgba(220, 53, 69, 0.2)',
  },
  logoutIcon: {
    marginRight: 8,
  },
  logoutText: {
    ...theme.typography.bodyBold,
    color: COLORS.danger,
    fontSize: 15,
    textTransform: 'none',
  },
});

export default MenuScreen;
