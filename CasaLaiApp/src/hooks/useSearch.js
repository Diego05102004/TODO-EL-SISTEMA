import { useState, useCallback } from 'react';

export const useSearch = (initialData = {}) => {
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState({});

  const filterData = useCallback((data) => {
    if (!searchQuery.trim()) {
      setSearchResults(data);
      return data;
    }
    
    const filtered = {};
    const query = searchQuery.toLowerCase().trim();
    
    Object.entries(data).forEach(([key, items]) => {
      const filteredItems = items.filter(item => 
        (item.name && item.name.toLowerCase().includes(query)) ||
        (item.description && item.description.toLowerCase().includes(query)) ||
        (item.category && item.category.toLowerCase().includes(query))
      );
      
      if (filteredItems.length > 0) {
        filtered[key] = filteredItems;
      }
    });
    
    setSearchResults(filtered);
    return filtered;
  }, [searchQuery]);

  const resetSearch = useCallback(() => {
    setSearchQuery('');
    setSearchResults({});
  }, []);

  return {
    searchQuery,
    setSearchQuery,
    searchResults,
    filterData,
    resetSearch,
    hasSearchQuery: !!searchQuery.trim()
  };
};

export default useSearch;
