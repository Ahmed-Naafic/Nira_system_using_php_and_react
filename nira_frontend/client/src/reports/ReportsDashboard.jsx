import { useState, useEffect } from 'react';
import { getSummaryReport, getCitizenReport, getRegistrationReport, getUserReport } from '../services/reportService';

const ReportsDashboard = () => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [summary, setSummary] = useState(null);
  const [citizenReport, setCitizenReport] = useState(null);
  const [registrationReport, setRegistrationReport] = useState(null);
  const [userReport, setUserReport] = useState(null);
  const [period, setPeriod] = useState('month');

  useEffect(() => {
    fetchAllReports();
  }, [period]);

  const fetchAllReports = async () => {
    setLoading(true);
    setError('');

    try {
      // Fetch all reports in parallel
      const [summaryData, citizenData, registrationData, userData] = await Promise.all([
        getSummaryReport(),
        getCitizenReport(),
        getRegistrationReport(period),
        getUserReport(),
      ]);

      if (summaryData.success) {
        setSummary(summaryData.data);
      }
      if (citizenData.success) {
        setCitizenReport(citizenData.data);
      }
      if (registrationData.success) {
        setRegistrationReport(registrationData.data);
      }
      if (userData.success) {
        setUserReport(userData.data);
      }
    } catch (err) {
      setError(
        err.response?.data?.message ||
        err.message ||
        'Failed to load reports'
      );
    } finally {
      setLoading(false);
    }
  };

  const StatCard = ({ title, value, subtitle, icon, color = 'blue' }) => {
    const colorClasses = {
      blue: 'bg-blue-50 border-blue-200 text-blue-900',
      green: 'bg-green-50 border-green-200 text-green-900',
      purple: 'bg-purple-50 border-purple-200 text-purple-900',
      orange: 'bg-orange-50 border-orange-200 text-orange-900',
      red: 'bg-red-50 border-red-200 text-red-900',
    };

    const iconColorClasses = {
      blue: 'text-blue-600',
      green: 'text-green-600',
      purple: 'text-purple-600',
      orange: 'text-orange-600',
      red: 'text-red-600',
    };

    return (
      <div className={`p-6 rounded-xl border ${colorClasses[color]} shadow-sm`}>
        <div className="flex items-center justify-between mb-2">
          <h3 className="text-sm font-medium opacity-80">{title}</h3>
          {icon && (
            <div className={iconColorClasses[color]}>
              {icon}
            </div>
          )}
        </div>
        <p className="text-3xl font-bold mb-1">{value?.toLocaleString() || '0'}</p>
        {subtitle && <p className="text-sm opacity-70">{subtitle}</p>}
      </div>
    );
  };

  if (loading) {
    return (
      <div className="max-w-7xl mx-auto">
        <div className="bg-white shadow rounded-lg p-6">
          <div className="text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p className="mt-4 text-gray-600">Loading reports...</p>
          </div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="max-w-7xl mx-auto">
        <div className="bg-red-50 border border-red-200 rounded-lg p-6">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="text-lg font-semibold text-red-900 mb-1">Error Loading Reports</h3>
              <p className="text-red-700">{error}</p>
            </div>
            <button
              onClick={fetchAllReports}
              className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-150"
            >
              Retry
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Reports Dashboard</h1>
          <p className="text-gray-600">System statistics and analytics</p>
        </div>
      </div>

      {/* Citizen Summary Cards */}
      {citizenReport && (
        <div>
          <h2 className="text-xl font-semibold text-gray-900 mb-4">Citizen Statistics</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <StatCard
              title="Total Citizens"
              value={citizenReport.total}
              subtitle="Active records"
              color="blue"
              icon={
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
              }
            />
            <StatCard
              title="Male"
              value={citizenReport.male}
              subtitle={`${citizenReport.total > 0 ? ((citizenReport.male / citizenReport.total) * 100).toFixed(1) : 0}% of total`}
              color="blue"
            />
            <StatCard
              title="Female"
              value={citizenReport.female}
              subtitle={`${citizenReport.total > 0 ? ((citizenReport.female / citizenReport.total) * 100).toFixed(1) : 0}% of total`}
              color="purple"
            />
            <StatCard
              title="Active"
              value={citizenReport.active}
              subtitle="Currently active"
              color="green"
            />
            <StatCard
              title="Deceased"
              value={citizenReport.deceased}
              subtitle="Status: DECEASED"
              color="red"
            />
          </div>
        </div>
      )}

      {/* Registration Summary */}
      {registrationReport && registrationReport.summary && (
        <div>
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-xl font-semibold text-gray-900">Civil Registration Summary</h2>
            <select
              value={period}
              onChange={(e) => setPeriod(e.target.value)}
              className="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="day">By Day</option>
              <option value="month">By Month</option>
              <option value="year">By Year</option>
            </select>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <StatCard
              title="Births"
              value={registrationReport.summary.births}
              subtitle="Total registered"
              color="green"
              icon={
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
              }
            />
            <StatCard
              title="Marriages"
              value={registrationReport.summary.marriages}
              subtitle="Total registered"
              color="purple"
            />
            <StatCard
              title="Divorces"
              value={registrationReport.summary.divorces}
              subtitle="Total registered"
              color="orange"
            />
            <StatCard
              title="Deaths"
              value={registrationReport.summary.deaths}
              subtitle="Total registered"
              color="red"
            />
          </div>
        </div>
      )}

      {/* Time-Based Charts Section */}
      {registrationReport && registrationReport.timeBased && (
        <div>
          <h2 className="text-xl font-semibold text-gray-900 mb-4">Registration Trends</h2>
          <div className="bg-white rounded-xl shadow-md border border-gray-200 p-6">
            {registrationReport.timeBased.citizens && registrationReport.timeBased.citizens.length > 0 && (
              <div className="mb-6">
                <h3 className="text-lg font-medium text-gray-900 mb-4">Citizen Registrations ({period})</h3>
                <div className="space-y-2">
                  {registrationReport.timeBased.citizens.slice(0, 10).map((item, index) => {
                    const count = item.total || item.count || 0;
                    const label = item.label || item.period || '';
                    const maxCount = Math.max(...registrationReport.timeBased.citizens.map(c => c.total || c.count || 0), 1);
                    return (
                      <div key={index} className="flex items-center justify-between">
                        <span className="text-sm text-gray-700">{label}</span>
                        <div className="flex items-center gap-3">
                          <div className="w-48 bg-gray-200 rounded-full h-4 relative overflow-hidden">
                            <div
                              className="bg-blue-600 h-4 rounded-full transition-all duration-300"
                              style={{
                                width: `${Math.min((count / maxCount) * 100, 100)}%`
                              }}
                            />
                          </div>
                          <span className="text-sm font-semibold text-gray-900 w-12 text-right">{count}</span>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            )}
            {registrationReport.timeBased.births && registrationReport.timeBased.births.length > 0 && (
              <div className="mb-6">
                <h3 className="text-lg font-medium text-gray-900 mb-4">Birth Registrations ({period})</h3>
                <div className="space-y-2">
                  {registrationReport.timeBased.births.slice(0, 10).map((item, index) => {
                    const count = item.total || item.count || 0;
                    const label = item.label || item.period || '';
                    const maxCount = Math.max(...registrationReport.timeBased.births.map(b => b.total || b.count || 0), 1);
                    return (
                      <div key={index} className="flex items-center justify-between">
                        <span className="text-sm text-gray-700">{label}</span>
                        <div className="flex items-center gap-3">
                          <div className="w-48 bg-gray-200 rounded-full h-4 relative overflow-hidden">
                            <div
                              className="bg-green-600 h-4 rounded-full transition-all duration-300"
                              style={{
                                width: `${Math.min((count / maxCount) * 100, 100)}%`
                              }}
                            />
                          </div>
                          <span className="text-sm font-semibold text-gray-900 w-12 text-right">{count}</span>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            )}
            {registrationReport.timeBased.marriages && registrationReport.timeBased.marriages.length > 0 && (
              <div className="mb-6">
                <h3 className="text-lg font-medium text-gray-900 mb-4">Marriage Registrations ({period})</h3>
                <div className="space-y-2">
                  {registrationReport.timeBased.marriages.slice(0, 10).map((item, index) => {
                    const count = item.total || item.count || 0;
                    const label = item.label || item.period || '';
                    const maxCount = Math.max(...registrationReport.timeBased.marriages.map(m => m.total || m.count || 0), 1);
                    return (
                      <div key={index} className="flex items-center justify-between">
                        <span className="text-sm text-gray-700">{label}</span>
                        <div className="flex items-center gap-3">
                          <div className="w-48 bg-gray-200 rounded-full h-4 relative overflow-hidden">
                            <div
                              className="bg-purple-600 h-4 rounded-full transition-all duration-300"
                              style={{
                                width: `${Math.min((count / maxCount) * 100, 100)}%`
                              }}
                            />
                          </div>
                          <span className="text-sm font-semibold text-gray-900 w-12 text-right">{count}</span>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            )}
            {registrationReport.timeBased.deaths && registrationReport.timeBased.deaths.length > 0 && (
              <div>
                <h3 className="text-lg font-medium text-gray-900 mb-4">Death Registrations ({period})</h3>
                <div className="space-y-2">
                  {registrationReport.timeBased.deaths.slice(0, 10).map((item, index) => {
                    const count = item.total || item.count || 0;
                    const label = item.label || item.period || '';
                    const maxCount = Math.max(...registrationReport.timeBased.deaths.map(d => d.total || d.count || 0), 1);
                    return (
                      <div key={index} className="flex items-center justify-between">
                        <span className="text-sm text-gray-700">{label}</span>
                        <div className="flex items-center gap-3">
                          <div className="w-48 bg-gray-200 rounded-full h-4 relative overflow-hidden">
                            <div
                              className="bg-red-600 h-4 rounded-full transition-all duration-300"
                              style={{
                                width: `${Math.min((count / maxCount) * 100, 100)}%`
                              }}
                            />
                          </div>
                          <span className="text-sm font-semibold text-gray-900 w-12 text-right">{count}</span>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            )}
          </div>
        </div>
      )}

      {/* User Activity Summary */}
      {userReport && (
        <div>
          <h2 className="text-xl font-semibold text-gray-900 mb-4">User Activity Summary</h2>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <StatCard
              title="Total Users"
              value={userReport.total}
              subtitle="All system users"
              color="blue"
              icon={
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
              }
            />
            <StatCard
              title="Active Users"
              value={userReport.active}
              subtitle="Currently active"
              color="green"
            />
            <StatCard
              title="Deleted Users"
              value={userReport.deleted}
              subtitle="Soft-deleted"
              color="red"
            />
          </div>
          {userReport.byRole && Object.keys(userReport.byRole).length > 0 && (
            <div className="mt-4 bg-white rounded-xl shadow-md border border-gray-200 p-6">
              <h3 className="text-lg font-medium text-gray-900 mb-4">Users by Role</h3>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {Object.entries(userReport.byRole).map(([role, count]) => (
                  <div key={role} className="p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <p className="text-sm text-gray-600 mb-1">{role}</p>
                    <p className="text-2xl font-bold text-gray-900">{count}</p>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default ReportsDashboard;

