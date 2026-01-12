import { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { getNotices, deleteNotice, createNotice } from '../services/noticeService';

const SystemNotices = () => {
  const { hasPermission } = useAuth();
  const [notices, setNotices] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [newNotice, setNewNotice] = useState({
    title: '',
    message: '',
    type: 'INFO',
    expiresAt: ''
  });

  useEffect(() => {
    if (hasPermission('VIEW_NOTICES')) {
      fetchNotices();
    }
  }, [hasPermission]);

  const fetchNotices = async () => {
    try {
      setLoading(true);
      setError('');
      const data = await getNotices();
      if (data.success) {
        setNotices(data.data || []);
      } else {
        setError(data.message || 'Failed to load notices');
      }
    } catch (err) {
      console.error('Error fetching notices:', err);
      const errorMessage = err.response?.data?.message || err.message || 'Failed to load notices';
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (noticeId) => {
    if (!window.confirm('Are you sure you want to delete this notice?')) {
      return;
    }

    try {
      const data = await deleteNotice(noticeId);
      if (data.success) {
        fetchNotices(); // Refresh list
      }
    } catch (err) {
      alert(err.response?.data?.message || 'Failed to delete notice');
    }
  };

  const handleCreate = async (e) => {
    e.preventDefault();
    setError('');

    try {
      // Prepare notice data
      let noticeData = {
        title: newNotice.title.trim(),
        message: newNotice.message.trim(),
        type: newNotice.type
      };
      
      // Handle expiresAt: convert datetime-local format to MySQL format
      if (newNotice.expiresAt && newNotice.expiresAt.trim() !== '') {
        let expiresAt = newNotice.expiresAt.trim();
        
        // datetime-local input format: "YYYY-MM-DDTHH:mm" (e.g., "2024-12-31T23:59")
        // MySQL datetime format needs: "YYYY-MM-DD HH:mm:ss" (e.g., "2024-12-31 23:59:00")
        
        console.log('Original expiresAt value:', expiresAt);
        
        // Check if it contains time separator 'T'
        if (expiresAt.includes('T')) {
          // Split by 'T' to separate date and time
          const parts = expiresAt.split('T');
          if (parts.length === 2) {
            const datePart = parts[0];
            const timePart = parts[1];
            
            // Ensure time part has seconds
            let finalTime = timePart;
            if (timePart.match(/^\d{2}:\d{2}$/)) {
              // Has HH:mm, add seconds
              finalTime = timePart + ':00';
            } else if (timePart.match(/^\d{2}:\d{2}:\d{2}$/)) {
              // Already has HH:mm:ss
              finalTime = timePart;
            }
            
            noticeData.expiresAt = `${datePart} ${finalTime}`;
            console.log('Converted expiresAt:', noticeData.expiresAt);
          } else {
            // Invalid format
            throw new Error('Invalid date/time format');
          }
        } else if (expiresAt.match(/^\d{4}-\d{2}-\d{2}$/)) {
          // Only date provided, add end of day time
          noticeData.expiresAt = expiresAt + ' 23:59:59';
        } else {
          // Try to use as-is (might already be in correct format)
          noticeData.expiresAt = expiresAt;
        }
      }
      // If expiresAt is empty, don't include it in the request

      console.log('Sending notice data:', noticeData);
      const data = await createNotice(noticeData);
      if (data.success) {
        setShowCreateForm(false);
        setNewNotice({ title: '', message: '', type: 'INFO', expiresAt: '' });
        fetchNotices(); // Refresh list
      }
    } catch (err) {
      console.error('Error creating notice:', err);
      setError(err.response?.data?.message || err.message || 'Failed to create notice');
    }
  };

  const getTypeColor = (type) => {
    switch (type) {
      case 'WARNING':
        return 'bg-yellow-50 border-yellow-200 text-yellow-900';
      case 'ALERT':
        return 'bg-red-50 border-red-200 text-red-900';
      case 'INFO':
      default:
        return 'bg-blue-50 border-blue-200 text-blue-900';
    }
  };

  const getTypeIcon = (type) => {
    switch (type) {
      case 'WARNING':
        return (
          <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
          </svg>
        );
      case 'ALERT':
        return (
          <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
          </svg>
        );
      default:
        return (
          <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
          </svg>
        );
    }
  };

  if (!hasPermission('VIEW_NOTICES')) {
    return null;
  }

  if (loading) {
    return (
      <div className="bg-white rounded-xl shadow-md border border-gray-200 p-6">
        <div className="animate-pulse">
          <div className="h-4 bg-gray-200 rounded w-1/4 mb-4"></div>
          <div className="h-20 bg-gray-200 rounded"></div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold text-gray-900">System Notices</h2>
        {hasPermission('MANAGE_NOTICES') && (
          <button
            onClick={() => setShowCreateForm(!showCreateForm)}
            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150"
          >
            {showCreateForm ? 'Cancel' : 'Create Notice'}
          </button>
        )}
      </div>

      {error && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
          {error}
        </div>
      )}

      {showCreateForm && hasPermission('MANAGE_NOTICES') && (
        <div className="bg-white rounded-xl shadow-md border border-gray-200 p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Create New Notice</h3>
          <form onSubmit={handleCreate} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Title</label>
              <input
                type="text"
                value={newNotice.title}
                onChange={(e) => setNewNotice({ ...newNotice, title: e.target.value })}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Message</label>
              <textarea
                value={newNotice.message}
                onChange={(e) => setNewNotice({ ...newNotice, message: e.target.value })}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                rows={3}
                required
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select
                  value={newNotice.type}
                  onChange={(e) => setNewNotice({ ...newNotice, type: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="INFO">Info</option>
                  <option value="WARNING">Warning</option>
                  <option value="ALERT">Alert</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Expires At (optional)</label>
                <input
                  type="datetime-local"
                  value={newNotice.expiresAt}
                  onChange={(e) => {
                    const value = e.target.value;
                    console.log('DateTime input changed:', value);
                    setNewNotice({ ...newNotice, expiresAt: value });
                  }}
                  min={new Date().toISOString().slice(0, 16)}
                  step="60"
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Select date and time"
                />
                <p className="text-xs text-gray-500 mt-1">
                  Leave empty for no expiration • Format: YYYY-MM-DD HH:MM
                </p>
                {newNotice.expiresAt && (
                  <p className="text-xs text-blue-600 mt-1">
                    Selected: {newNotice.expiresAt}
                  </p>
                )}
              </div>
            </div>
            <button
              type="submit"
              className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150"
            >
              Create Notice
            </button>
          </form>
        </div>
      )}

      {notices.length === 0 ? (
        <div className="bg-white rounded-xl shadow-md border border-gray-200 p-6 text-center text-gray-500">
          No active notices
        </div>
      ) : (
        <div className="space-y-3">
          {notices.map((notice) => (
            <div
              key={notice.id}
              className={`rounded-xl border p-4 ${getTypeColor(notice.type)}`}
            >
              <div className="flex items-start justify-between">
                <div className="flex items-start gap-3 flex-1">
                  <div className="mt-0.5">{getTypeIcon(notice.type)}</div>
                  <div className="flex-1">
                    <h3 className="font-semibold mb-1">{notice.title}</h3>
                    <p className="text-sm opacity-90 whitespace-pre-wrap">{notice.message}</p>
                    <p className="text-xs opacity-70 mt-2">
                      Created by {notice.createdByUsername} • {new Date(notice.createdAt).toLocaleString()}
                      {notice.expiresAt && ` • Expires: ${new Date(notice.expiresAt).toLocaleString()}`}
                    </p>
                  </div>
                </div>
                {hasPermission('MANAGE_NOTICES') && (
                  <button
                    onClick={() => handleDelete(notice.id)}
                    className="ml-4 text-red-600 hover:text-red-800 transition-colors"
                    title="Delete notice"
                  >
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default SystemNotices;

