import React from 'react';
import { View, Text, StyleSheet, ScrollView } from 'react-native';
import COLORS from '../../constants/colors';
import theme from '../../constants/theme';

const ProductsScreen = () => {
  return (
    <ScrollView style={styles.container}>
      <View style={styles.content}>
        <Text style={styles.title}>Productos</Text>
        <Text style={styles.subtitle}>Gestión de productos</Text>
        
        <View style={styles.card}>
          <Text style={styles.cardText}>Aquí podrás administrar todos tus productos</Text>
          <Text style={styles.cardHint}>Próximamente: Lista de productos, búsqueda y filtros</Text>
        </View>
      </View>
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  content: {
    flex: 1,
    padding: theme.spacing.lg,
  },
  title: {
    ...theme.typography.h1,
    color: COLORS.primary,
    marginBottom: theme.spacing.sm,
  },
  subtitle: {
    ...theme.typography.body,
    color: COLORS.textSecondary,
    marginBottom: theme.spacing.xl,
  },
  card: {
    backgroundColor: COLORS.white,
    borderRadius: theme.radius.md,
    padding: theme.spacing.lg,
    marginBottom: theme.spacing.lg,
    ...theme.shadow.sm,
  },
  cardText: {
    ...theme.typography.body,
    color: COLORS.text,
    marginBottom: theme.spacing.sm,
  },
  cardHint: {
    ...theme.typography.small,
    color: COLORS.textSecondary,
    fontStyle: 'italic',
  },
});

export default ProductsScreen;
