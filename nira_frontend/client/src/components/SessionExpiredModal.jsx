import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';

const SessionExpiredModal = ({ onLogin }) => {
  const navigate = useNavigate();
  const [countdown, setCountdown] = useState(3);

  useEffect(() => {
    // Countdown timer before redirecting to login
    const timer = setInterval(() => {
      setCountdown((prev) => {
        if (prev <= 1) {
          clearInterval(timer);
          if (onLogin) {
            onLogin();
          } else {
            navigate('/login', { 
              state: { 
                message: 'Your session has expired. Please login again.' 
              } 
            });
          }
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    return () => clearInterval(timer);
  }, [navigate, onLogin]);

  return (
    <div className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 sm:p-8">
        <div className="text-center">
          {/* Icon */}
          <div className="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
            <svg
              className="h-10 w-10 text-red-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
              />
            </svg>
          </div>

          {/* Title */}
          <h2 className="text-2xl font-bold text-gray-900 mb-2">
            Session Expired
          </h2>

          {/* Message */}
          <p className="text-gray-600 mb-6">
            Your session has expired due to inactivity. Please login again to continue.
          </p>

          {/* Countdown */}
          <div className="mb-6">
            <p className="text-sm text-gray-500">
              Redirecting to login page in{' '}
              <span className="font-semibold text-blue-600 text-lg">{countdown}</span> seconds...
            </p>
          </div>

          {/* Button */}
          <button
            onClick={() => {
              if (onLogin) {
                onLogin();
              } else {
                navigate('/login', { 
                  state: { 
                    message: 'Your session has expired. Please login again.' 
                  } 
                });
              }
            }}
            className="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 shadow-sm hover:shadow-md font-medium"
          >
            Go to Login Page
          </button>
        </div>
      </div>
    </div>
  );
};

export default SessionExpiredModal;
