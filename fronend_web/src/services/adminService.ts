import { springApi } from './api';

// Admin Service - chỉ export functions
const adminService = {
  // Dashboard Stats
  getStats: () => springApi.get('/api/admin/stats'),
  getUsersByRole: () => springApi.get('/api/admin/stats/users-by-role'),
  getRecentActivity: () => springApi.get('/api/admin/stats/activity'),

  // User Management
  getUsers: (page = 0, size = 10, sortBy = 'createdAt', sortDir = 'desc') =>
    springApi.get('/api/admin/users', {
      params: { page, size, sortBy, sortDir },
    }),

  searchUsers: (keyword: string, page = 0, size = 10) =>
    springApi.get('/api/admin/users/search', {
      params: { keyword, page, size },
    }),

  filterUsersByRole: (role: string, page = 0, size = 10) =>
    springApi.get('/api/admin/users/filter', {
      params: { role, page, size },
    }),

  getUserById: (id: number) => springApi.get(`/api/admin/users/${id}`),

  changeUserRole: (id: number, role: string) =>
    springApi.put(`/api/admin/users/${id}/role`, { role }),

  deleteUser: (id: number) => springApi.delete(`/api/admin/users/${id}`),

  getRecentUsers: () => springApi.get('/api/admin/users/recent'),
};

export { adminService };
export default adminService;
