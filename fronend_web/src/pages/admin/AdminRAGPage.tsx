import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { motion } from 'framer-motion';
import { 
  Database, Search, Plus, ChevronLeft, Trash2, Edit2, 
  Save, X, FileText, Tag, RefreshCw
} from 'lucide-react';
import { fastApi } from '../../services/api';
import toast from 'react-hot-toast';

interface RAGDocument {
  id: string;
  content: string;
  metadata: {
    source?: string;
    topic?: string;
    category?: string;
    [key: string]: any;
  };
}

export default function AdminRAGPage() {
  const [documents, setDocuments] = useState<RAGDocument[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState<any[]>([]);
  const [showAddModal, setShowAddModal] = useState(false);
  const [editingDoc, setEditingDoc] = useState<RAGDocument | null>(null);
  
  // Form state
  const [newContent, setNewContent] = useState('');
  const [newSource, setNewSource] = useState('');
  const [newTopic, setNewTopic] = useState('');
  const [newCategory, setNewCategory] = useState('');

  useEffect(() => {
    loadDocuments();
  }, []);

  const loadDocuments = async () => {
    try {
      setLoading(true);
      const response = await fastApi.get('/api/documents');
      // API trả về format: { documents: [], metadatas: [], ids: [], count: n }
      const data = response.data;
      const docs: RAGDocument[] = [];
      
      if (data.ids && data.documents && data.metadatas) {
        for (let i = 0; i < data.ids.length; i++) {
          docs.push({
            id: data.ids[i],
            content: data.documents[i],
            metadata: data.metadatas[i] || {}
          });
        }
      }
      
      setDocuments(docs);
    } catch (error) {
      console.error('Error loading documents:', error);
      toast.error('Không thể tải danh sách documents');
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = async () => {
    if (!searchQuery.trim()) return;
    
    try {
      const response = await fastApi.post('/api/documents/search', {
        query: searchQuery,
        top_k: 10
      });
      setSearchResults(response.data.results || []);
      toast.success(`Tìm thấy ${response.data.results?.length || 0} kết quả`);
    } catch (error) {
      console.error('Error searching:', error);
      toast.error('Lỗi tìm kiếm');
    }
  };

  const handleAdd = async () => {
    if (!newContent.trim()) {
      toast.error('Vui lòng nhập nội dung');
      return;
    }

    try {
      await fastApi.post('/api/documents/add', {
        documents: [newContent],
        metadatas: [{
          source: newSource || 'admin',
          topic: newTopic || 'general',
          category: newCategory || 'knowledge'
        }]
      });
      toast.success('Đã thêm document mới');
      setShowAddModal(false);
      resetForm();
      loadDocuments();
    } catch (error) {
      console.error('Error adding:', error);
      toast.error('Không thể thêm document');
    }
  };

  const handleUpdate = async () => {
    if (!editingDoc) return;

    try {
      await fastApi.put(`/api/documents/${editingDoc.id}`, {
        content: newContent,
        metadata: {
          source: newSource,
          topic: newTopic,
          category: newCategory
        }
      });
      toast.success('Đã cập nhật document');
      setEditingDoc(null);
      resetForm();
      loadDocuments();
    } catch (error) {
      console.error('Error updating:', error);
      toast.error('Không thể cập nhật document');
    }
  };

  const handleDelete = async (id: string) => {
    if (!confirm('Bạn có chắc muốn xóa document này?')) return;

    try {
      await fastApi.delete(`/api/documents/${id}`);
      toast.success('Đã xóa document');
      loadDocuments();
    } catch (error) {
      console.error('Error deleting:', error);
      toast.error('Không thể xóa document');
    }
  };

  const startEdit = (doc: RAGDocument) => {
    setEditingDoc(doc);
    setNewContent(doc.content);
    setNewSource(doc.metadata?.source || '');
    setNewTopic(doc.metadata?.topic || '');
    setNewCategory(doc.metadata?.category || '');
  };

  const resetForm = () => {
    setNewContent('');
    setNewSource('');
    setNewTopic('');
    setNewCategory('');
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <Link to="/admin" className="p-2 hover:bg-gray-100 rounded-lg">
                <ChevronLeft className="w-5 h-5" />
              </Link>
              <div className="p-2 bg-purple-100 rounded-lg">
                <Database className="w-6 h-6 text-purple-600" />
              </div>
              <div>
                <h1 className="text-2xl font-bold text-gray-900">Quản lý RAG Knowledge Base</h1>
                <p className="text-gray-500">Tổng: {documents.length} documents</p>
              </div>
            </div>
            <button
              onClick={() => setShowAddModal(true)}
              className="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
            >
              <Plus className="w-5 h-5" />
              Thêm Document
            </button>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Search RAG */}
        <div className="bg-white rounded-xl shadow-sm border p-4 mb-6">
          <h3 className="font-medium text-gray-900 mb-3">Test RAG Search</h3>
          <div className="flex gap-4">
            <div className="flex-1 relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                type="text"
                placeholder="Nhập câu hỏi để test RAG..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                className="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
              />
            </div>
            <button
              onClick={handleSearch}
              className="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
            >
              Tìm kiếm
            </button>
          </div>
          
          {searchResults.length > 0 && (
            <div className="mt-4 space-y-2">
              <h4 className="text-sm font-medium text-gray-700">Kết quả ({searchResults.length}):</h4>
              {searchResults.map((result, idx) => (
                <div key={idx} className="p-3 bg-purple-50 rounded-lg text-sm">
                  <p className="text-gray-800">{result.content?.substring(0, 200)}...</p>
                  <p className="text-purple-600 text-xs mt-1">
                    Score: {result.score?.toFixed(3)} | Source: {result.metadata?.source}
                  </p>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Documents List */}
        <div className="bg-white rounded-xl shadow-sm border overflow-hidden">
          <div className="px-6 py-4 border-b flex items-center justify-between">
            <h3 className="font-semibold text-gray-900">Danh sách Documents</h3>
            <button
              onClick={loadDocuments}
              className="p-2 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg"
            >
              <RefreshCw className="w-5 h-5" />
            </button>
          </div>

          {loading ? (
            <div className="p-8 text-center">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto"></div>
            </div>
          ) : (
            <div className="divide-y">
              {documents.map((doc) => (
                <div key={doc.id} className="p-4 hover:bg-gray-50">
                  {editingDoc?.id === doc.id ? (
                    // Edit Mode
                    <div className="space-y-3">
                      <textarea
                        value={newContent}
                        onChange={(e) => setNewContent(e.target.value)}
                        className="w-full p-3 border rounded-lg h-32"
                        placeholder="Nội dung..."
                      />
                      <div className="grid grid-cols-3 gap-3">
                        <input
                          type="text"
                          value={newSource}
                          onChange={(e) => setNewSource(e.target.value)}
                          className="p-2 border rounded-lg"
                          placeholder="Source"
                        />
                        <input
                          type="text"
                          value={newTopic}
                          onChange={(e) => setNewTopic(e.target.value)}
                          className="p-2 border rounded-lg"
                          placeholder="Topic"
                        />
                        <input
                          type="text"
                          value={newCategory}
                          onChange={(e) => setNewCategory(e.target.value)}
                          className="p-2 border rounded-lg"
                          placeholder="Category"
                        />
                      </div>
                      <div className="flex gap-2">
                        <button
                          onClick={handleUpdate}
                          className="flex items-center gap-1 px-3 py-1.5 bg-purple-600 text-white rounded-lg text-sm"
                        >
                          <Save className="w-4 h-4" /> Lưu
                        </button>
                        <button
                          onClick={() => { setEditingDoc(null); resetForm(); }}
                          className="flex items-center gap-1 px-3 py-1.5 bg-gray-200 text-gray-700 rounded-lg text-sm"
                        >
                          <X className="w-4 h-4" /> Hủy
                        </button>
                      </div>
                    </div>
                  ) : (
                    // View Mode
                    <div className="flex items-start justify-between gap-4">
                      <div className="flex-1 min-w-0">
                        <p className="text-gray-800 text-sm line-clamp-2">{doc.content}</p>
                        <div className="flex items-center gap-3 mt-2">
                          <span className="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-xs">
                            <Tag className="w-3 h-3" />
                            {doc.metadata?.topic || 'general'}
                          </span>
                          <span className="text-xs text-gray-500">
                            Source: {doc.metadata?.source || 'unknown'}
                          </span>
                          <span className="text-xs text-gray-400">
                            ID: {doc.id.substring(0, 8)}...
                          </span>
                        </div>
                      </div>
                      <div className="flex items-center gap-1">
                        <button
                          onClick={() => startEdit(doc)}
                          className="p-2 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg"
                        >
                          <Edit2 className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => handleDelete(doc.id)}
                          className="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Add Modal */}
      {showAddModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <div className="bg-white rounded-xl w-full max-w-lg mx-4 p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Thêm Document mới</h3>
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Nội dung *</label>
                <textarea
                  value={newContent}
                  onChange={(e) => setNewContent(e.target.value)}
                  className="w-full p-3 border rounded-lg h-32"
                  placeholder="Nhập nội dung kiến thức..."
                />
              </div>
              <div className="grid grid-cols-3 gap-3">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Source</label>
                  <input
                    type="text"
                    value={newSource}
                    onChange={(e) => setNewSource(e.target.value)}
                    className="w-full p-2 border rounded-lg"
                    placeholder="admin"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Topic</label>
                  <input
                    type="text"
                    value={newTopic}
                    onChange={(e) => setNewTopic(e.target.value)}
                    className="w-full p-2 border rounded-lg"
                    placeholder="python"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Category</label>
                  <input
                    type="text"
                    value={newCategory}
                    onChange={(e) => setNewCategory(e.target.value)}
                    className="w-full p-2 border rounded-lg"
                    placeholder="programming"
                  />
                </div>
              </div>
            </div>
            <div className="flex justify-end gap-3 mt-6">
              <button
                onClick={() => { setShowAddModal(false); resetForm(); }}
                className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg"
              >
                Hủy
              </button>
              <button
                onClick={handleAdd}
                className="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
              >
                Thêm
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
