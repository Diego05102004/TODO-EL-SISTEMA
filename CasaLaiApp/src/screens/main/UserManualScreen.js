import React from 'react';
import { View, Text, StyleSheet, ScrollView } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import COLORS from '../../constants/colors';
import theme from '../../constants/theme';
import { Ionicons } from '@expo/vector-icons';

const UserManualScreen = () => {
  const sections = [
    {
      title: 'Bienvenido a Casa Lai',
      content: 'Esta aplicación te permite explorar productos, hacer pedidos y gestionar tu cuenta de manera sencilla.',
    },
    {
      title: 'Navegación',
      content: '• Inicio: Explora el catálogo de productos\n• Pedidos: Consulta tu historial de compras\n• Carrito: Gestiona tus productos seleccionados\n• Perfil: Actualiza tu información personal\n• Menú: Accede a todas las opciones adicionales',
    },
    {
      title: 'Catálogo de Productos',
      content: '• Navega por productos y combos\n• Usa filtros por categoría, marca y precio\n• Toca un producto para ver detalles\n• Agrega productos al carrito directamente',
    },
    {
      title: 'Carrito de Compras',
      content: '• Revisa los productos seleccionados\n• Modifica cantidades según disponibilidad\n• El sistema controla el stock automáticamente\n• Procede al pago cuando estés listo',
    },
    {
      title: 'Realizar Pedidos',
      content: '• Completa los datos de pago\n• Sube el comprobante de pago\n• Recibe confirmación del pedido\n• Consulta el estado en "Mis Pedidos"',
    },
    {
      title: 'Perfil de Usuario',
      content: '• Actualiza tu información personal\n• Cambia tu foto de perfil\n• Modifica tu correo y contraseña\n• Todos los cambios requieren contraseña actual',
    },
    {
      title: 'Seguridad',
      content: '• Tu sesión expira automáticamente\n• Puedes extender la sesión hasta 3 veces\n• Usa contraseñas seguras\n• No compartas tus credenciales',
    },
  ];

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <ScrollView style={styles.content}>
        {sections.map((section, index) => (
          <View key={index} style={styles.section}>
            <View style={styles.sectionHeader}>
              <Ionicons name="information-circle" size={24} color={COLORS.primary} />
              <Text style={styles.sectionTitle}>{section.title}</Text>
            </View>
            <Text style={styles.sectionContent}>{section.content}</Text>
          </View>
        ))}

        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Ionicons name="help-circle" size={24} color={COLORS.primary} />
            <Text style={styles.sectionTitle}>¿Necesitas ayuda?</Text>
          </View>
          <Text style={styles.sectionContent}>
            Si tienes alguna pregunta o problema, contáctanos a través de la sección de Ayuda en el menú.
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
    padding: theme.spacing.lg,
    marginBottom: theme.spacing.md,
    borderWidth: 1,
    borderColor: COLORS.lightGray,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: theme.spacing.sm,
  },
  sectionTitle: {
    ...theme.typography.subtitle,
    color: COLORS.primary,
    fontWeight: 'bold',
    marginLeft: theme.spacing.sm,
  },
  sectionContent: {
    ...theme.typography.body,
    color: COLORS.text,
    lineHeight: 22,
  },
});

export default UserManualScreen;
