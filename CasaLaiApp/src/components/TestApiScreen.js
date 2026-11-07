import React, { useState } from 'react';
import { View, Text, Button, Alert, StyleSheet, ActivityIndicator } from 'react-native';
import COLORS from '../constants/colors';

const TestApiScreen = () => {
  const [loading, setLoading] = useState(false);
  const [respuesta, setRespuesta] = useState(null);

  // REEMPLAZA CON LA URL DE TU HOSTING REAL O DE LOCALTUNNEL
  const API_URL = 'https://fine-feet-wear.loca.lt/proyecto-casalai-ca/api/test_ping.php';

  const enviarPeticionHTTPS = async () => {
    setLoading(true);
    setRespuesta(null);

    try {
      const response = await fetch(API_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'User-Agent': 'CasaLaiApp/1.0',
          'Bypass-Tunnel-Reminder': 'true',
        },
        body: JSON.stringify({
          mensaje: 'Mensaje de prueba enviado desde CasaLaiApp (APK)',
        }),
      });

      if (!response.ok) {
        throw new Error(`Error en servidor. Código HTTP: ${response.status}`);
      }

      const data = await response.json();
      setRespuesta(data);
      Alert.alert('¡Conexión Exitosa!', data.mensaje_servidor);
    } catch (error) {
      console.error('Error al conectar:', error);
      Alert.alert('Error de Comunicación', error.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Prueba de Petición HTTPS</Text>
      
      {loading ? (
        <ActivityIndicator size="large" color={COLORS.primary || '#000'} />
      ) : (
        <Button 
          title="Enviar mensaje al Hosting" 
          onPress={enviarPeticionHTTPS} 
          color={COLORS.primary}
        />
      )}

      {respuesta && (
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Respuesta de la API:</Text>
          <Text><Text style={styles.bold}>Estado:</Text> {respuesta.status}</Text>
          <Text><Text style={styles.bold}>Mensaje:</Text> {respuesta.mensaje_servidor}</Text>
          <Text><Text style={styles.bold}>Echo datos:</Text> {respuesta.datos_recibidos}</Text>
          <Text><Text style={styles.bold}>Fecha Servidor:</Text> {respuesta.timestamp}</Text>
        </View>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 20, backgroundColor: '#fff' },
  title: { fontSize: 18, fontWeight: 'bold', marginBottom: 20 },
  card: { marginTop: 20, padding: 15, backgroundColor: '#f0f0f0', borderRadius: 8, width: '100%' },
  cardTitle: { fontWeight: 'bold', marginBottom: 10, fontSize: 16 },
  bold: { fontWeight: 'bold' }
});

export default TestApiScreen;