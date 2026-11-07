import React, { useState } from 'react';
import { View, StyleSheet, TouchableOpacity, TextInput, Image } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import { useSelector } from 'react-redux';
import COLORS from '../../constants/colors';
import { buildProfileImageUrl } from '../../config/apiConfig';

const CustomHeader = ({ onSearch }) => {
  const [searchQuery, setSearchQuery] = useState('');
  const navigation = useNavigation();
  const { user } = useSelector((state) => state.auth);
  const profileImage = buildProfileImageUrl(user?.foto_perfil || user?.avatar || user?.photoURL);

  const handleSearch = () => {
    if (onSearch && searchQuery.trim()) {
      onSearch(searchQuery.trim());
    }
  };

  return (
    <View style={styles.headerContainer}>
      <View style={styles.searchContainer}>
        <Ionicons name="search" size={20} color={COLORS.gray} style={styles.searchIcon} />
        <TextInput
          style={styles.searchInput}
          placeholder="Buscar productos..."
          placeholderTextColor={COLORS.gray}
          value={searchQuery}
          onChangeText={setSearchQuery}
          onSubmitEditing={handleSearch}
          returnKeyType="search"
        />
        {searchQuery.length > 0 && (
          <TouchableOpacity onPress={() => setSearchQuery('')} style={styles.clearButton}>
            <Ionicons name="close-circle" size={20} color={COLORS.gray} />
          </TouchableOpacity>
        )}
      </View>
      
      <TouchableOpacity 
        onPress={() => navigation.navigate('Profile')}
        style={styles.profileButton}
      >
        {profileImage ? (
          <Image 
            source={{ uri: profileImage }} 
            style={styles.avatar} 
            resizeMode="cover"
          />
        ) : (
          <Ionicons name="person-circle-outline" size={32} color={COLORS.primary} />
        )}
      </TouchableOpacity>
    </View>
  );
};

const styles = StyleSheet.create({
  headerContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 15,
    paddingVertical: 10,
    backgroundColor: COLORS.white,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
  },
  searchContainer: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.lightGray,
    borderRadius: 20,
    paddingHorizontal: 15,
    marginRight: 10,
    height: 40,
  },
  searchIcon: {
    marginRight: 10,
  },
  searchInput: {
    flex: 1,
    fontSize: 14,
    color: COLORS.darkGray,
    padding: 0,
    height: '100%',
  },
  clearButton: {
    padding: 5,
  },
  profileButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    justifyContent: 'center',
    alignItems: 'center',
    overflow: 'hidden',
  },
  avatar: {
    width: '100%',
    height: '100%',
    borderRadius: 20,
  },
});

export default CustomHeader;
