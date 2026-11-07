import React from 'react';
import {
  View,
  TextInput,
  Text,
  StyleSheet,
  TouchableOpacity,
} from 'react-native';
import { COLORS } from '../../constants/colors';
import theme from '../../constants/theme';
import { sanitizeText } from '../../sanitization';

const Input = ({
  label,
  value,
  onChangeText,
  placeholder,
  secureTextEntry = false,
  error = null,
  touched = false,
  style,
  inputStyle,
  labelStyle,
  errorStyle,
  rightIcon,
  onRightIconPress,
  sanitize = true, // Nueva prop para habilitar/deshabilitar sanitización
  ...props
}) => {
  const showError = error && touched;

  const handleTextChange = (text) => {
    if (sanitize && !secureTextEntry) {
      // Sanitizar el texto para prevenir inyecciones
      const sanitized = sanitizeText(text);
      onChangeText(sanitized);
    } else {
      onChangeText(text);
    }
  };

  return (
    <View style={[styles.container, style]}>
      {label && (
        <Text style={[styles.label, labelStyle]}>{label}</Text>
      )}
      
      <View style={[
        styles.inputContainer,
        showError && styles.inputContainerError,
      ]}>
        <TextInput
          style={[styles.input, inputStyle]}
          value={value}
          onChangeText={handleTextChange}
          placeholder={placeholder}
          placeholderTextColor={COLORS.placeholder}
          secureTextEntry={secureTextEntry}
          {...props}
        />
        
        {rightIcon && (
          <TouchableOpacity
            onPress={onRightIconPress}
            style={styles.rightIconContainer}
          >
            {rightIcon}
          </TouchableOpacity>
        )}
      </View>
      
      {showError && (
        <Text style={[styles.errorText, errorStyle]}>{error}</Text>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    marginBottom: theme.spacing.md,
  },
  label: {
    ...theme.typography.body,
    color: COLORS.text,
    marginBottom: theme.spacing.xs,
    fontWeight: '500',
  },
  inputContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.white,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: theme.radius.md,
    paddingHorizontal: theme.spacing.md,
  },
  inputContainerError: {
    borderColor: COLORS.danger,
  },
  input: {
    flex: 1,
    height: 50,
    ...theme.typography.body,
    color: COLORS.text,
    paddingVertical: 0,
  },
  rightIconContainer: {
    marginLeft: theme.spacing.sm,
  },
  errorText: {
    ...theme.typography.caption,
    color: COLORS.danger,
    marginTop: theme.spacing.xs,
  },
});

export default Input;
