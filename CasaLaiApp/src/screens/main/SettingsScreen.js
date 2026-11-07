import React, { useState } from 'react';
import { View, Text, StyleSheet, ScrollView, Switch } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useSelector } from 'react-redux';
import COLORS from '../../constants/colors';
import theme from '../../constants/theme';
import { Ionicons } from '@expo/vector-icons';

const SettingsScreen = () => {
  const { user } = useSelector((state) => state.auth);
  const [notifications, setNotifications] = useState(true);
  const [darkMode, setDarkMode] = useState(false);
  const [autoLogin, setAutoLogin] = useState(false);

  const settingsSections = [
    {
      title: 'Preferencias',
      items: [
        {
          id: 'notifications',
          title: 'Notificaciones',
          description: 'Recibir alertas de pedidos y promociones',
          type: 'switch',
          value: notifications,
          onValueChange: setNotifications,
          icon: 'notifications-outline',
        },
        {
          id: 'darkMode',
          title: 'Modo Oscuro',
          description: 'Usar tema oscuro en la aplicación',
          type: 'switch',
          value: darkMode,
          onValueChange: setDarkMode,
          icon: 'moon-outline',
        },
        {
          id: 'autoLogin',
          title: 'Inicio de Sesión Automático',
          description: 'Mantener sesión iniciada',
          type: 'switch',
          value: autoLogin,
          onValueChange: setAutoLogin,
          icon: 'lock-closed-outline',
        },
      ],
    },
    {
      title: 'Información',
      items: [
        {
          id: 'version',
          title: 'Versión de la App',
          description: '1.0.0',
          type: 'info',
          icon: 'information-circle-outline',
        },
        {
          id: 'api',
          title: 'URL de la API',
          description: 'Configuración del servidor',
          type: 'info',
          icon: 'server-outline',
        },
      ],
    },
  ];

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <ScrollView style={styles.content}>
        {settingsSections.map((section, sectionIndex) => (
          <View key={sectionIndex} style={styles.section}>
            <Text style={styles.sectionTitle}>{section.title}</Text>
            {section.items.map((item) => (
              <View key={item.id} style={styles.settingItem}>
                <View style={styles.settingLeft}>
                  <Ionicons
                    name={item.icon}
                    size={24}
                    color={item.color || COLORS.primary}
                    style={styles.settingIcon}
                  />
                  <View style={styles.settingTextContainer}>
                    <Text style={[styles.settingTitle, item.color && { color: item.color }]}>
                      {item.title}
                    </Text>
                    <Text style={styles.settingDescription}>{item.description}</Text>
                  </View>
                </View>
                {item.type === 'switch' && (
                  <Switch
                    value={item.value}
                    onValueChange={item.onValueChange}
                    trackColor={{ false: COLORS.lightGray, true: COLORS.primary }}
                    thumbColor={item.value ? COLORS.white : COLORS.white}
                  />
                )}
                {item.type === 'button' && (
                  <Ionicons name="chevron-forward" size={20} color={COLORS.textSecondary} />
                )}
              </View>
            ))}
          </View>
        ))}

        <View style={styles.infoSection}>
          <Text style={styles.infoText}>
            Casa Lai App v1.0.0
          </Text>
          <Text style={styles.infoSubtext}>
            Desarrollado con React Native y Expo
          </Text>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  content: {
    flex: 1,
    padding: theme.spacing.md,
  },
  section: {
    backgroundColor: COLORS.white,
    borderRadius: 12,
    marginBottom: theme.spacing.md,
    borderWidth: 1,
    borderColor: COLORS.lightGray,
    overflow: 'hidden',
  },
  sectionTitle: {
    ...theme.typography.subtitle,
    color: COLORS.textSecondary,
    fontWeight: 'bold',
    padding: theme.spacing.md,
    paddingBottom: theme.spacing.sm,
  },
  settingItem: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: theme.spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.lightGray,
  },
  settingLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  settingIcon: {
    marginRight: theme.spacing.md,
  },
  settingTextContainer: {
    flex: 1,
  },
  settingTitle: {
    ...theme.typography.bodyBold,
    color: COLORS.text,
    marginBottom: 2,
  },
  settingDescription: {
    ...theme.typography.caption,
    color: COLORS.textSecondary,
  },
  infoSection: {
    padding: theme.spacing.lg,
    alignItems: 'center',
    marginTop: theme.spacing.md,
  },
  infoText: {
    ...theme.typography.body,
    color: COLORS.textSecondary,
    marginBottom: theme.spacing.xs,
  },
  infoSubtext: {
    ...theme.typography.caption,
    color: COLORS.textSecondary,
  },
});

export default SettingsScreen;
