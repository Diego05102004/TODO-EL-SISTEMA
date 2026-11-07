import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ScrollView,
  Platform,
  LayoutAnimation,
  UIManager,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import COLORS from '../../constants/colors';
import theme from '../../constants/theme';

/*if (Platform.OS === 'android' && UIManager.setLayoutAnimationEnabledExperimental) {
  UIManager.setLayoutAnimationEnabledExperimental(true);
}*/

const helpSections = [
  {
    id: 'catalog',
    title: 'Catálogo y Combos',
    icon: 'grid-outline',
    description: 'Aprende a navegar, buscar productos y ver detalles de los combos.',
    steps: [
      'Busca productos usando la barra de búsqueda en la parte superior.',
      'Explora las categorías disponibles y filtra por tipo de producto.',
      'Haz clic sobre la tarjeta de un Combo para ver el modal con la lista completa de productos incluidos, sus cantidades y detalles.',
      'Presiona el botón "Agregar" para añadir ítems directamente a tu carrito.',
    ],
  },
  {
    id: 'cart',
    title: 'Carrito de Compras',
    icon: 'cart-outline',
    description: 'Gestiona tus artículos seleccionados antes de confirmar tu orden.',
    steps: [
      'Accede al carrito tocando el ícono de carrito en la barra de navegación inferior.',
      'Aumenta o disminuye la cantidad de cada ítem usando los botones "+" y "-".',
      'Elimina productos que ya no desees adquirir con el ícono de papelera.',
      'Verifica el resumen del monto subtotal y total.',
      'Presiona "Generar Pedido" para proceder con el pago y registro.',
    ],
  },
  {
    id: 'orders',
    title: 'Mis Pedidos y Pagos',
    icon: 'receipt-outline',
    description: 'Consulta tus facturas, verifica el estado de tus compras y registra tus pagos.',
    steps: [
      'Accede a la sección tocando "Pedidos" en la barra de navegación inferior.',
      'Revisa el listado de facturas clasificadas según su estado (Borrador, En Proceso, etc.) con su número de factura (FAC-XX), fecha y monto en bolívares (Bs.S).',
      'En los pedidos con estado "Borrador", presiona el botón "Pagar" para iniciar el proceso de reporte de pago.',
      'Selecciona el método de pago de tu preferencia (Pago Móvil, Transferencia o Zelle) para visualizar las cuentas bancarias disponibles.',
      'Verifica la información en el "Resumen para pagar" y presiona el botón "Confirmar pago".',
      'Una vez reportado, la factura pasará al estado "En Proceso" mientras un administrador valida el pago.',
    ],
  },
  {
    id: 'profile',
    title: 'Mi Perfil',
    icon: 'person-outline',
    description: 'Actualiza tu información personal.',
    steps: [
      'Accede a "Mi Perfil" desde el ícono de usuario en la parte superior derecha en la pantalla de inicio.',
      'Edita su imagen de perfil tocando su imagen actual. Selecciona una nueva imagen desde tu galería y presiona "Guardar Foto".',
      'Edita tus datos personales como nombre de usuario, nombre completo y número de teléfono.',
      'En la sección de Configuración puedes actualizar el correo electrónico y tu contraseña.',
    ],
  },
  {
    id: 'support',
    title: 'Soporte y Contacto',
    icon: 'chatbubbles-outline',
    description: 'Canales de atención al cliente e información adicional.',
    steps: [
      'Si presentas problemas con un pedido, puedes comunicarte con el equipo de soporte.',
      'Asegúrate de tener a la mano el número de factura de tu pedido.',
    ],
  },
];

const HelpScreen = () => {
  const navigation = useNavigation();
  const [expandedId, setExpandedId] = useState(null);

  const toggleSection = (id) => {
    if (Platform.OS !== 'web') {
      LayoutAnimation.configureNext(LayoutAnimation.Presets.easeInEaseOut);
    }
    setExpandedId(expandedId === id ? null : id);
  };

  const Container = Platform.OS === 'web' ? View : SafeAreaView;
  const containerProps = Platform.OS === 'web' ? {} : { edges: ['top'] };

  return (
    <Container style={styles.container} {...containerProps}>
      <View style={styles.header}>
        <TouchableOpacity style={styles.backButton} onPress={() => navigation.goBack()}>
          <Ionicons name="arrow-back" size={24} color={COLORS.white} />
        </TouchableOpacity>
        <Text style={styles.title}>Manual de Usuario</Text>
        <View style={{ width: 24 }} />
      </View>

      <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
        <View style={styles.introCard}>
          <Ionicons name="help-buoy-outline" size={32} color={COLORS.primary} style={styles.introIcon} />
          <View style={styles.introTextContainer}>
            <Text style={styles.introTitle}>¿Cómo utilizar la app?</Text>
            <Text style={styles.introSub}>
              Selecciona cualquier módulo para desplegar las instrucciones de uso.
            </Text>
          </View>
        </View>

        {helpSections.map((section) => {
          const isExpanded = expandedId === section.id;
          return (
            <View key={section.id} style={styles.accordionContainer}>
              <TouchableOpacity
                style={[styles.accordionHeader, isExpanded && styles.accordionHeaderActive]}
                onPress={() => toggleSection(section.id)}
                activeOpacity={0.7}
              >
                <Ionicons name={section.icon} size={22} color={COLORS.primary} style={styles.sectionIcon} />
                <View style={styles.headerTextContainer}>
                  <Text style={styles.sectionTitle}>{section.title}</Text>
                  <Text style={styles.sectionSub}>{section.description}</Text>
                </View>
                <Ionicons
                  name={isExpanded ? 'chevron-up' : 'chevron-down'}
                  size={20}
                  color={COLORS.textSecondary}
                />
              </TouchableOpacity>

              {isExpanded && (
                <View style={styles.accordionBody}>
                  {section.steps.map((step, index) => (
                    <View key={index} style={styles.stepRow}>
                      <View style={styles.bullet}>
                        <Text style={styles.bulletText}>{index + 1}</Text>
                      </View>
                      <Text style={styles.stepText}>{step}</Text>
                    </View>
                  ))}
                </View>
              )}
            </View>
          );
        })}
      </ScrollView>
    </Container>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  header: {
    backgroundColor: COLORS.primary,
    padding: theme.spacing.md,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderBottomLeftRadius: 16,
    borderBottomRightRadius: 16,
    elevation: 3,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
  },
  backButton: {
    padding: 4,
  },
  title: {
    ...theme.typography.h3,
    color: COLORS.white,
    fontWeight: 'bold',
  },
  content: {
    flex: 1,
    padding: theme.spacing.md,
  },
  introCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.white,
    padding: theme.spacing.md,
    borderRadius: 12,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: COLORS.lightGray,
  },
  introIcon: {
    marginRight: 12,
  },
  introTextContainer: {
    flex: 1,
  },
  introTitle: {
    ...theme.typography.subtitle,
    color: COLORS.text,
    fontWeight: 'bold',
  },
  introSub: {
    ...theme.typography.caption,
    color: COLORS.textSecondary,
    marginTop: 2,
  },
  accordionContainer: {
    backgroundColor: COLORS.white,
    borderRadius: 10,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: COLORS.lightGray,
    overflow: 'hidden',
  },
  accordionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 14,
  },
  accordionHeaderActive: {
    borderBottomWidth: 1,
    borderBottomColor: COLORS.lightGray,
    backgroundColor: '#F8F9FA',
  },
  sectionIcon: {
    marginRight: 12,
  },
  headerTextContainer: {
    flex: 1,
  },
  sectionTitle: {
    ...theme.typography.bodyBold,
    color: COLORS.text,
  },
  sectionSub: {
    ...theme.typography.caption,
    color: COLORS.textSecondary,
    marginTop: 2,
  },
  accordionBody: {
    padding: 14,
    backgroundColor: COLORS.white,
  },
  stepRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    marginBottom: 10,
  },
  bullet: {
    width: 22,
    height: 22,
    borderRadius: 11,
    backgroundColor: COLORS.primary,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 10,
    marginTop: 1,
  },
  bulletText: {
    color: COLORS.white,
    fontSize: 12,
    fontWeight: 'bold',
  },
  stepText: {
    ...theme.typography.body,
    flex: 1,
    color: COLORS.text,
    fontSize: 13,
    lineHeight: 18,
  },
});

export default HelpScreen;