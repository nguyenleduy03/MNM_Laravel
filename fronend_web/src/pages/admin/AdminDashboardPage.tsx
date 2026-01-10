import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { motion } from 'framer-motion';
import { 
  Users, BookOpen, FileQuestion, MessageSquare, 
  Layers, CreditCard, TrendingUp, Clock,
  ChevronRight, Shield
} from 'lucide-react';
import { adminService } from '../../services/adminService';

// Types định nghĩa local
interface DashboardStats {
  totalUsers: number;
  totalCourses: number;
  totalLessons: number;
  totalQuizzes: number;
  totalChatSessions: number;
  totalChatMessages: number;
  totalFlashcardDecks: number;
  totalFlashcards: number;
}

interface UsersByRole {
  USER: number;
  ADMIN: number;
  TEACHER: number;
  STUDENT: number;
}

interface RecentActivity {
  usersToday: number;
  usersThisWeek: number;
  usersThisMonth: number;
  coursesToday: number;
  coursesThisWeek: number;
  coursesThisMonth: number;
  messagesThisWeek: number;
}

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

// Stats Card Component
const StatsCard = ({ 
  title, 
  value, 
  icon: Icon, 
  color, 
  trend 
}: { 
  title: string; 
  value: number; 
  icon: React.ElementType; 
  color: string;
  trend?: string;
}) => (
  <motion.div
    initial={{ opacity: 0, y: 20 }}
    animate={{ opacity: 1, y: 0 }}
    className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow"
  >
    <div className="flex items-center justify-between">
      <div>
        <p className="text-sm text-gray-500 mb-1">{title}</p>
        <p className="text-3xl font-bold text-gray-900">{value.toLocaleString()}</p>
        {trend && (
          <p className="text-sm text-green-600 mt-1 flex items-center gap-1">
            <TrendingUp className="w-4 h-4" />
            {trend}
          </p>
        )}
      </div>
      <div className={`p-4 rounded-xl ${color}`}>
        <Icon className="w-8 h-8 text-white" />
      </div>
    </div>
  </motion.div>
);

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

export default function AdminDashboardPage() {
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [usersByRole, setUsersByRole] = useState<UsersByRole | null>(null);
  const [activity, setActivity] = useState<RecentActivity | null>(null);
  const [recentUsers, setRecentUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadDashboardData();
  }, []);

  const loadDashboardData = async () => {
    try {
      setLoading(true);
      const [statsRes, roleRes, activityRes, usersRes] = await Promise.all([
        adminService.getStats(),
        adminService.getUsersByRole(),
        adminService.getRecentActivity(),
        adminService.getRecentUsers(),
      ]);
      setStats(statsRes.data);
      setUsersByRole(roleRes.data);
      setActivity(activityRes.data);
      setRecentUsers(usersRes.data);
    } catch (error) {
      console.error('Error loading dashboard:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-indigo-100 rounded-lg">
              <Shield className="w-6 h-6 text-indigo-600" />
            </div>
            <div>
              <h1 className="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
              <p className="text-gray-500">Quản lý hệ thống Agent For Edu</p>
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Stats Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <StatsCard
            title="Tổng Users"
            value={stats?.totalUsers || 0}
            icon={Users}
            color="bg-indigo-500"
            trend={activity ? `+${activity.usersThisWeek} tuần này` : undefined}
          />
          <StatsCard
            title="Khóa học"
            value={stats?.totalCourses || 0}
            icon={BookOpen}
            color="bg-emerald-500"
          />
          <StatsCard
            title="Bài Quiz"
            value={stats?.totalQuizzes || 0}
            icon={FileQuestion}
            color="bg-amber-500"
          />
          <StatsCard
            title="Tin nhắn Chat"
            value={stats?.totalChatMessages || 0}
            icon={MessageSquare}
            color="bg-rose-500"
          />
        </div>

        {/* Second Row Stats */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <StatsCard
            title="Bài học"
            value={stats?.totalLessons || 0}
            icon={Layers}
            color="bg-cyan-500"
          />
          <StatsCard
            title="Phiên Chat"
            value={stats?.totalChatSessions || 0}
            icon={MessageSquare}
            color="bg-purple-500"
          />
          <StatsCard
            title="Bộ Flashcard"
            value={stats?.totalFlashcardDecks || 0}
            icon={CreditCard}
            color="bg-pink-500"
          />
          <StatsCard
            title="Thẻ Flashcard"
            value={stats?.totalFlashcards || 0}
            icon={CreditCard}
            color="bg-orange-500"
          />
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Users by Role */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
            className="bg-white rounded-xl shadow-sm border border-gray-100 p-6"
          >
            <h2 className="text-lg font-semibold text-gray-900 mb-4">Users theo Role</h2>
            <div className="space-y-4">
              {usersByRole && Object.entries(usersByRole).map(([role, count]) => (
                <div key={role} className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <RoleBadge role={role} />
                    <span className="text-gray-600">{role}</span>
                  </div>
                  <span className="font-semibold text-gray-900">{count}</span>
                </div>
              ))}
            </div>
          </motion.div>

          {/* Recent Activity */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.3 }}
            className="bg-white rounded-xl shadow-sm border border-gray-100 p-6"
          >
            <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <Clock className="w-5 h-5 text-gray-400" />
              Hoạt động gần đây
            </h2>
            <div className="space-y-4">
              <div className="flex justify-between items-center py-2 border-b">
                <span className="text-gray-600">Users hôm nay</span>
                <span className="font-semibold text-indigo-600">+{activity?.usersToday || 0}</span>
              </div>
              <div className="flex justify-between items-center py-2 border-b">
                <span className="text-gray-600">Users tuần này</span>
                <span className="font-semibold text-indigo-600">+{activity?.usersThisWeek || 0}</span>
              </div>
              <div className="flex justify-between items-center py-2 border-b">
                <span className="text-gray-600">Users tháng này</span>
                <span className="font-semibold text-indigo-600">+{activity?.usersThisMonth || 0}</span>
              </div>
              <div className="flex justify-between items-center py-2">
                <span className="text-gray-600">Chat tuần này</span>
                <span className="font-semibold text-emerald-600">{activity?.messagesThisWeek || 0}</span>
              </div>
            </div>
          </motion.div>

          {/* Quick Links */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.4 }}
            className="bg-white rounded-xl shadow-sm border border-gray-100 p-6"
          >
            <h2 className="text-lg font-semibold text-gray-900 mb-4">Quản lý nhanh</h2>
            <div className="space-y-3">
              <Link
                to="/admin/users"
                className="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors group"
              >
                <div className="flex items-center gap-3">
                  <Users className="w-5 h-5 text-indigo-500" />
                  <span className="text-gray-700">Quản lý Users</span>
                </div>
                <ChevronRight className="w-5 h-5 text-gray-400 group-hover:text-indigo-500" />
              </Link>
              <Link
                to="/admin/courses"
                className="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors group"
              >
                <div className="flex items-center gap-3">
                  <BookOpen className="w-5 h-5 text-emerald-500" />
                  <span className="text-gray-700">Quản lý Courses</span>
                </div>
                <ChevronRight className="w-5 h-5 text-gray-400 group-hover:text-emerald-500" />
              </Link>
              <Link
                to="/admin/rag"
                className="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors group"
              >
                <div className="flex items-center gap-3">
                  <Layers className="w-5 h-5 text-purple-500" />
                  <span className="text-gray-700">Quản lý RAG</span>
                </div>
                <ChevronRight className="w-5 h-5 text-gray-400 group-hover:text-purple-500" />
              </Link>
            </div>
          </motion.div>
        </div>

        {/* Recent Users Table */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.5 }}
          className="mt-8 bg-white rounded-xl shadow-sm border border-gray-100"
        >
          <div className="p-6 border-b border-gray-100 flex items-center justify-between">
            <h2 className="text-lg font-semibold text-gray-900">Users đăng ký gần đây</h2>
            <Link
              to="/admin/users"
              className="text-indigo-600 hover:text-indigo-700 text-sm font-medium"
            >
              Xem tất cả →
            </Link>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày tạo</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {recentUsers.map((user) => (
                  <tr key={user.id} className="hover:bg-gray-50">
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
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </motion.div>
      </div>
    </div>
  );
}
