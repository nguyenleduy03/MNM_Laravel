import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { motion } from 'framer-motion';
import { 
  Users, Search, Filter, ChevronLeft, ChevronRight,
  Trash2, UserCog, ArrowLeft
} from 'lucide-react';
import { adminService } from '../../services/adminService';
import toast from 'react-hot-toast';

// Types định nghĩa local
interface User {
  id: number;
  username: string;
  email: string;
  fullName: string;
  avatarUrl?: string;
  role: 'USER' | 'ADMIN' | 'TEACHER' | 'STUDENT';
  createdAt: string;
  updatedAt: string;
}

interface PaginatedUsers {
  users: User[];
  currentPage: number;
  totalItems: number;
  totalPages: number;
}

// Role Badge Component
const RoleBadge = ({ role }: { role: string }) => {
  const colors: Record<string, string> = {
    ADMIN: 'bg-red-100 text-red-700',
    TEACHER: 'bg-blue-100 text-blue-700',
    STUDENT: 'bg-green-100 text-green-700',
    USER: 'bg-gray-100 text-gray-700',
  };
  return (
    <span className={`px-2 py-1 rounded-full text-xs font-medium ${colors[role] || colors.USER}`}>
      {role}
    </span>
  );
};

export default function AdminUsersPage() {
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(0);
  const [totalPages, setTotalPages] = useState(0);
  const [totalItems, setTotalItems] = useState(0);
  const [searchKeyword, setSearchKeyword] = useState('');
  const [filterRole, setFilterRole] = useState('');
  const [selectedUser, setSelectedUser] = useState<User | null>(null);
  const [showRoleModal, setShowRoleModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);

  useEffect(() => {
    loadUsers();
  }, [currentPage, filterRole]);

  const loadUsers = async () => {
    try {
      setLoading(true);
      let response;
      
      if (searchKeyword) {
        response = await adminService.searchUsers(searchKeyword, currentPage);
      } else if (filterRole) {
        response = await adminService.filterUsersByRole(filterRole, currentPage);
      } else {
        response = await adminService.getUsers(currentPage);
      }
      
      setUsers(response.data.users);
      setTotalPages(response.data.totalPages);
      setTotalItems(response.data.totalItems);
    } catch (error) {
      console.error('Error loading users:', error);
      toast.error('Không thể tải danh sách users');
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    setCurrentPage(0);
    loadUsers();
  };

  const handleChangeRole = async (newRole: string) => {
    if (!selectedUser) return;
    
    try {
      await adminService.changeUserRole(selectedUser.id, newRole);
      toast.success(`Đã đổi role thành ${newRole}`);
      setShowRoleModal(false);
      loadUsers();
    } catch (error) {
      toast.error('Không thể đổi role');
    }
  };

  const handleDeleteUser = async () => {
    if (!selectedUser) return;
    
    try {
      await adminService.deleteUser(selectedUser.id);
      toast.success('Đã xóa user');
      setShowDeleteModal(false);
      loadUsers();
    } catch (error) {
      toast.error('Không thể xóa user');
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div className="flex items-center gap-4">
            <Link to="/admin" className="p-2 hover:bg-gray-100 rounded-lg">
              <ArrowLeft className="w-5 h-5 text-gray-600" />
            </Link>
            <div className="flex items-center gap-3">
              <div className="p-2 bg-indigo-100 rounded-lg">
                <Users className="w-6 h-6 text-indigo-600" />
              </div>
              <div>
                <h1 className="text-2xl font-bold text-gray-900">Quản lý Users</h1>
                <p className="text-gray-500">{totalItems} users trong hệ thống</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Search & Filter */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
          <div className="flex flex-col md:flex-row gap-4">
            <form onSubmit={handleSearch} className="flex-1 flex gap-2">
              <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input
                  type="text"
                  placeholder="Tìm theo username, email, tên..."
                  value={searchKeyword}
                  onChange={(e) => setSearchKeyword(e.target.value)}
                  className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                />
              </div>
              <button
                type="submit"
                className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
              >
                Tìm kiếm
              </button>
            </form>
            
            <div className="flex items-center gap-2">
              <Filter className="w-5 h-5 text-gray-400" />
              <select
                value={filterRole}
                onChange={(e) => {
                  setFilterRole(e.target.value);
                  setCurrentPage(0);
                }}
                className="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500"
              >
                <option value="">Tất cả roles</option>
                <option value="ADMIN">Admin</option>
                <option value="TEACHER">Teacher</option>
                <option value="STUDENT">Student</option>
                <option value="USER">User</option>
              </select>
            </div>
          </div>
        </div>

        {/* Users Table */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
        >
          {loading ? (
            <div className="p-8 text-center">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto"></div>
            </div>
          ) : (
            <>
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày tạo</th>
                      <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-100">
                    {users.map((user) => (
                      <tr key={user.id} className="hover:bg-gray-50">
                        <td className="px-6 py-4 text-gray-500">#{user.id}</td>
                        <td className="px-6 py-4">
                          <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                              <span className="text-indigo-600 font-medium">
                                {user.username.charAt(0).toUpperCase()}
                              </span>
                            </div>
                            <div>
                              <p className="font-medium text-gray-900">{user.username}</p>
                              <p className="text-sm text-gray-500">{user.fullName || 'N/A'}</p>
                            </div>
                          </div>
                        </td>
                        <td className="px-6 py-4 text-gray-600">{user.email}</td>
                        <td className="px-6 py-4">
                          <RoleBadge role={user.role} />
                        </td>
                        <td className="px-6 py-4 text-gray-500 text-sm">
                          {new Date(user.createdAt).toLocaleDateString('vi-VN')}
                        </td>
                        <td className="px-6 py-4">
                          <div className="flex items-center justify-end gap-2">
                            <button
                              onClick={() => {
                                setSelectedUser(user);
                                setShowRoleModal(true);
                              }}
                              className="p-2 hover:bg-gray-100 rounded-lg text-gray-600"
                              title="Đổi role"
                            >
                              <UserCog className="w-4 h-4" />
                            </button>
                            <button
                              onClick={() => {
                                setSelectedUser(user);
                                setShowDeleteModal(true);
                              }}
                              className="p-2 hover:bg-red-50 rounded-lg text-red-600"
                              title="Xóa user"
                            >
                              <Trash2 className="w-4 h-4" />
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              {/* Pagination */}
              <div className="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <p className="text-sm text-gray-500">
                  Hiển thị {users.length} / {totalItems} users
                </p>
                <div className="flex items-center gap-2">
                  <button
                    onClick={() => setCurrentPage(p => Math.max(0, p - 1))}
                    disabled={currentPage === 0}
                    className="p-2 hover:bg-gray-100 rounded-lg disabled:opacity-50"
                  >
                    <ChevronLeft className="w-5 h-5" />
                  </button>
                  <span className="px-4 py-2 text-sm">
                    Trang {currentPage + 1} / {totalPages || 1}
                  </span>
                  <button
                    onClick={() => setCurrentPage(p => Math.min(totalPages - 1, p + 1))}
                    disabled={currentPage >= totalPages - 1}
                    className="p-2 hover:bg-gray-100 rounded-lg disabled:opacity-50"
                  >
                    <ChevronRight className="w-5 h-5" />
                  </button>
                </div>
              </div>
            </>
          )}
        </motion.div>
      </div>

      {/* Change Role Modal */}
      {showRoleModal && selectedUser && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <motion.div
            initial={{ opacity: 0, scale: 0.95 }}
            animate={{ opacity: 1, scale: 1 }}
            className="bg-white rounded-xl p-6 w-full max-w-md mx-4"
          >
            <h3 className="text-lg font-semibold mb-4">Đổi Role cho {selectedUser.username}</h3>
            <p className="text-gray-600 mb-4">Role hiện tại: <RoleBadge role={selectedUser.role} /></p>
            <div className="grid grid-cols-2 gap-3 mb-6">
              {['USER', 'STUDENT', 'TEACHER', 'ADMIN'].map((role) => (
                <button
                  key={role}
                  onClick={() => handleChangeRole(role)}
                  disabled={role === selectedUser.role}
                  className={`p-3 rounded-lg border-2 transition-colors ${
                    role === selectedUser.role
                      ? 'border-gray-200 bg-gray-50 text-gray-400'
                      : 'border-gray-200 hover:border-indigo-500 hover:bg-indigo-50'
                  }`}
                >
                  {role}
                </button>
              ))}
            </div>
            <button
              onClick={() => setShowRoleModal(false)}
              className="w-full py-2 border border-gray-200 rounded-lg hover:bg-gray-50"
            >
              Hủy
            </button>
          </motion.div>
        </div>
      )}

      {/* Delete Confirmation Modal */}
      {showDeleteModal && selectedUser && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <motion.div
            initial={{ opacity: 0, scale: 0.95 }}
            animate={{ opacity: 1, scale: 1 }}
            className="bg-white rounded-xl p-6 w-full max-w-md mx-4"
          >
            <div className="text-center">
              <div className="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <Trash2 className="w-6 h-6 text-red-600" />
              </div>
              <h3 className="text-lg font-semibold mb-2">Xóa User?</h3>
              <p className="text-gray-600 mb-6">
                Bạn có chắc muốn xóa user <strong>{selectedUser.username}</strong>? 
                Hành động này không thể hoàn tác.
              </p>
              <div className="flex gap-3">
                <button
                  onClick={() => setShowDeleteModal(false)}
                  className="flex-1 py-2 border border-gray-200 rounded-lg hover:bg-gray-50"
                >
                  Hủy
                </button>
                <button
                  onClick={handleDeleteUser}
                  className="flex-1 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                >
                  Xóa
                </button>
              </div>
            </div>
          </motion.div>
        </div>
      )}
    </div>
  );
}
