import { useState, useCallback } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { loginUser, logoutUser, refreshAuthSession } from '../redux/slices/authSlice';

export const useAuth = () => {
  const [error, setError] = useState(null);
  const dispatch = useDispatch();
  const { user, isAuthenticated, loading } = useSelector(state => state.auth || {});

  const signIn = useCallback(async (credentials) => {
    try {
      setError(null);
      const result = await dispatch(loginUser({
        email: credentials.email,
        password: credentials.password,
        username: credentials.username,
      }));

      if (loginUser.fulfilled.match(result)) {
        return { success: true, user: result.payload.user };
      }

      const errorMessage = result.payload?.message || result.payload || 'Error de autenticación';
      setError(errorMessage);
      return { success: false, error: errorMessage };
    } catch (err) {
      const errorMessage = err.message || 'Error de autenticación';
      setError(errorMessage);
      return { success: false, error: errorMessage };
    }
  }, [dispatch]);

  const signOut = useCallback(async () => {
    await dispatch(logoutUser());
    await dispatch(refreshAuthSession());
  }, [dispatch]);

  const signUp = useCallback(async (userData) => {
    try {
      setError(null);
      // Simular registro de usuario
      await new Promise(resolve => setTimeout(resolve, 1000));
      // Aquí iría la lógica de registro real
      return { success: true };
    } catch (err) {
      const errorMessage = err.message || 'Error en el registro';
      setError(errorMessage);
      return { success: false, error: errorMessage };
    }
  }, []);

  return {
    user,
    isAuthenticated: !!isAuthenticated,
    loading: !!loading,
    error,
    signIn,
    signOut,
    signUp
  };
};

export default useAuth;
