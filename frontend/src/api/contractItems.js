import api from './axios';

/**
 * Mengambil daftar barang inventaris yang terkait dengan suatu kontrak.
 * Endpoint terlindungi: GET /api/kontrak/{id}/items
 * @param {string|number} contractId
 */
export const getContractItems = async (contractId) => {
  const response = await api.get(`/kontrak/${contractId}/items`);
  return response.data;
};

/**
 * Menambahkan relasi barang inventaris ke suatu kontrak (IT only).
 * Endpoint terlindungi: POST /api/kontrak/{id}/items
 * @param {string|number} contractId
 * @param {number} inventoryId - ID inventaris yang akan ditambahkan
 */
export const addContractItem = async (contractId, inventoryId) => {
  const response = await api.post(`/kontrak/${contractId}/items`, {
    inventory_id: inventoryId,
  });
  return response.data;
};

/**
 * Menghapus relasi barang inventaris dari suatu kontrak (IT only).
 * Endpoint terlindungi: DELETE /api/kontrak/{id}/items/{itemId}
 * @param {string|number} contractId
 * @param {string|number} itemId - ID contract_item (bukan inventory_id)
 */
export const removeContractItem = async (contractId, itemId) => {
  const response = await api.delete(`/kontrak/${contractId}/items/${itemId}`);
  return response.data;
};

