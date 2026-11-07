import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import COLORS from '../../constants/colors';
import theme from '../../constants/theme';

const IncomeExpenseScreen = () => {
  return (
    <View style={styles.container}>
      <Text style={styles.title}>Ingresos y Egresos</Text>
      <Text style={styles.subtitle}>Control de movimientos financieros</Text>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: COLORS.background,
    padding: theme.spacing.lg,
  },
  title: {
    ...theme.typography.h1,
    color: COLORS.primary,
    marginBottom: theme.spacing.md,
  },
  subtitle: {
    ...theme.typography.body,
    color: COLORS.textSecondary,
  },
});

export default IncomeExpenseScreen;
