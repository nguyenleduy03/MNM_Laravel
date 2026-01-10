import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { motion } from 'framer-motion';
import { 
  BookOpen, Search, Filter, ChevronLeft, ChevronRight,
  Eye, Trash2, Users, Calendar, Shield
} from 'lucide-react';
import { springApi } from '../../services/api';
import toast from 'react-hot-toast';

interface Course {
  id: number;
  title: string;
  description: string;
  thumbnailUrl?: string;
  createdAt: string;
  updatedAt: string;
  instructor?: {
    id: number;
    username: string;
    fullName: string;
  };
  enrollmentCount?: number;
}

export default function AdminCoursesPage() {
  const [courses, setCourses] = useState<Course[]>([]);
  const [loading, setLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(0);
  const [totalPages, setTotalPages] = useState(0);
  const [totalItems, setTotalItems] = useState(0);
  const [searchKeyword, setSearchKeyword] = useState('');

  useEffect(() => {
    loadCourses();
  }, [currentPage]);

  const loadCourses = async () => {
    try {
      setLoading(true);
      const response = await springApi.get('/api/courses', {
        params: { page: currentPage, size: 10 }
      });
      
      // API có thể trả về array hoặc object với pagination
      if (Array.isArray(response.data)) {
        setCourses(response.data);
        setTotalPages(1);
        setTotalItems(response.data.length);
      } else {
        setCourses(response.data.content || response.data.courses || []);
        setTotalPages(response.data.totalPages || 1);
        setTotalItems(response.data.totalElements || response.data.totalItems || 0);
      }
    } catch (error) {
      console.error('Error loading courses:', error);
      toast.error('Không thể tải danh sách khóa học');
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = async () => {
    if (!searchKeyword.trim()) {
      loadCourses();
      return;
    }
    try {
      setLoading(true);
      const response = await springApi.get('/api/courses/search', {
        params: { keyword: searchKeyword }
      });
      setCourses(Array.isArray(response.data) ? response.data : []);
      setTotalPages(1);
    } catch (error) {
      console.error('Error searching:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Bạn có chắc muốn xóa khóa học này?')) return;
    
    try {
      await springApi.delete(`/api/courses/${id}`);
      toast.success('Đã xóa khóa học');
      loadCourses();
    } catch (error) {
      console.error('Error deleting:', error);
      toast.error('Không thể xóa khóa học');
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div className="flex items-center gap-3">
            <Link to="/admin" className="p-2 hover:bg-gray-100 rounded-lg">
              <ChevronLeft className="w-5 h-5" />
            </Link>
            <div className="p-2 bg-emerald-100 rounded-lg">
              <BookOpen className="w-6 h-6 text-emerald-600" />
            </div>
            <div>
              <h1 className="text-2xl font-bold text-gray-900">Quản lý Khóa học</h1>
              <p className="text-gray-500">Tổng: {totalItems} khóa học</p>
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Search */}
        <div className="bg-white rounded-xl shadow-sm border p-4 mb-6">
          <div className="flex gap-4">
            <div className="flex-1 relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                type="text"
                placeholder="Tìm kiếm khóa học..."
                value={searchKeyword}
                onChange={(e) => setSearchKeyword(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                className="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
              />
            </div>
            <button
              onClick={handleSearch}
              className="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700"
            >
              Tìm kiếm
            </button>
          </div>
        </div>

        {/* Courses Table */}
        <div className="bg-white rounded-xl shadow-sm border overflow-hidden">
          {loading ? (
            <div className="p-8 text-center">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-600 mx-auto"></div>
            </div>
          ) : (
            <table className="w-full">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Khóa học</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Giảng viên</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày tạo</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {courses.map((course) => (
                  <tr key={course.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-3">
                        <div className="w-12 h-12 rounded-lg bg-emerald-100 flex items-center justify-center overflow-hidden">
                          {course.thumbnailUrl ? (
                            <img src={course.thumbnailUrl} alt="" className="w-full h-full object-cover" />
                          ) : (
                            <BookOpen className="w-6 h-6 text-emerald-600" />
                          )}
                        </div>
                        <div>
                          <p className="font-medium text-gray-900">{course.title}</p>
                          <p className="text-sm text-gray-500 line-clamp-1">{course.description}</p>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-gray-600">
                      {course.instructor?.fullName || course.instructor?.username || 'N/A'}
                    </td>
                    <td className="px-6 py-4 text-gray-500 text-sm">
                      {new Date(course.createdAt).toLocaleDateString('vi-VN')}
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center justify-end gap-2">
                        <Link
                          to={`/courses/${course.id}`}
                          className="p-2 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg"
                          title="Xem chi tiết"
                        >
                          <Eye className="w-5 h-5" />
                        </Link>
                        <button
                          onClick={() => handleDelete(course.id)}
                          className="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg"
                          title="Xóa"
                        >
                          <Trash2 className="w-5 h-5" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}

          {/* Pagination */}
          {totalPages > 1 && (
            <div className="px-6 py-4 border-t flex items-center justify-between">
              <p className="text-sm text-gray-500">
                Trang {currentPage + 1} / {totalPages}
              </p>
              <div className="flex gap-2">
                <button
                  onClick={() => setCurrentPage(p => Math.max(0, p - 1))}
                  disabled={currentPage === 0}
                  className="p-2 rounded-lg border hover:bg-gray-50 disabled:opacity-50"
                >
                  <ChevronLeft className="w-5 h-5" />
                </button>
                <button
                  onClick={() => setCurrentPage(p => Math.min(totalPages - 1, p + 1))}
                  disabled={currentPage >= totalPages - 1}
                  className="p-2 rounded-lg border hover:bg-gray-50 disabled:opacity-50"
                >
                  <ChevronRight className="w-5 h-5" />
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
