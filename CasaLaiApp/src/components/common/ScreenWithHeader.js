import React from 'react';
import { View, StyleSheet } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import CustomHeader from './CustomHeader';
import COLORS from '../../constants/colors';

const ScreenWithHeader = ({ children, onSearch, style, headerShown = true }) => {
  return (
    <View style={[styles.container, style]}>
      {headerShown && (
        <View style={styles.headerContainer}>
          <CustomHeader onSearch={onSearch} />
        </View>
      )}
      <SafeAreaView 
        style={styles.safeArea}
        edges={['right', 'left', 'bottom']}
      >
        <View style={styles.content}>
          {children}
        </View>
      </SafeAreaView>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  headerContainer: {
    backgroundColor: COLORS.white,
    zIndex: 1,
    // Sombra sutil que funciona en ambas plataformas
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    // Para Android
    elevation: 3,
  },
  safeArea: {
    flex: 1,
  },
  content: {
    flex: 1,
    padding: 16,
  },
});

export default ScreenWithHeader;
