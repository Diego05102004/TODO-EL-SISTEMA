import React from 'react';
import { TouchableOpacity, Text, ActivityIndicator, StyleSheet } from 'react-native';
import { COLORS } from '../../constants/colors';
import theme from '../../constants/theme';

const Button = ({
  title,
  onPress,
  loading = false,
  disabled = false,
  variant = 'primary',
  style,
  textStyle,
  ...props
}) => {
  const getButtonStyle = () => {
    switch (variant) {
      case 'secondary':
        return styles.secondaryButton;
      case 'outline':
        return styles.outlineButton;
      case 'text':
        return styles.textButton;
      case 'danger':
        return styles.dangerButton;
      default:
        return styles.primaryButton;
    }
  };

  const getTextStyle = () => {
    switch (variant) {
      case 'secondary':
        return styles.secondaryButtonText;
      case 'outline':
        return styles.outlineButtonText;
      case 'text':
        return styles.textButtonText;
      case 'danger':
        return styles.dangerButtonText;
      default:
        return styles.primaryButtonText;
    }
  };

  return (
    <TouchableOpacity
      style={[
        styles.button,
        getButtonStyle(),
        (loading || disabled) && styles.disabledButton,
        style,
      ]}
      onPress={onPress}
      disabled={loading || disabled}
      activeOpacity={0.8}
      {...props}
    >
      {loading ? (
        <ActivityIndicator
          color={
            variant === 'primary' || variant === 'danger'
              ? COLORS.white
              : COLORS.primary
          }
        />
      ) : (
        <Text style={[getTextStyle(), textStyle]}>{title}</Text>
      )}
    </TouchableOpacity>
  );
};

const styles = StyleSheet.create({
  button: {
    height: 50,
    borderRadius: theme.radius.md,
    justifyContent: 'center',
    alignItems: 'center',
    flexDirection: 'row',
    paddingHorizontal: theme.spacing.lg,
    ...theme.shadow.sm,
  },
  primaryButton: {
    backgroundColor: COLORS.primary,
  },
  secondaryButton: {
    backgroundColor: COLORS.secondary,
  },
  outlineButton: {
    backgroundColor: 'transparent',
    borderWidth: 1,
    borderColor: COLORS.primary,
  },
  textButton: {
    backgroundColor: 'transparent',
  },
  dangerButton: {
    backgroundColor: COLORS.danger,
  },
  disabledButton: {
    opacity: 0.6,
  },
  primaryButtonText: {
    ...theme.typography.button,
    color: COLORS.white,
  },
  secondaryButtonText: {
    ...theme.typography.button,
    color: COLORS.white,
  },
  outlineButtonText: {
    ...theme.typography.button,
    color: COLORS.primary,
  },
  textButtonText: {
    ...theme.typography.button,
    color: COLORS.primary,
  },
  dangerButtonText: {
    ...theme.typography.button,
    color: COLORS.white,
  },
});

export default Button;
