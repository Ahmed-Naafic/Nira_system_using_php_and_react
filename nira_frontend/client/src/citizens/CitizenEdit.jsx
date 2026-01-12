import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import api from '../api/api';
import { handleViewCitizenDetails } from './utils/citizenNavigation';

const CitizenEdit = () => {
  const navigate = useNavigate();
  const { nationalId } = useParams();
  // Calculate date range: 100 years ago to today
  const today = new Date();
  const hundredYearsAgo = new Date();
  hundredYearsAgo.setFullYear(today.getFullYear() - 100);
  const maxDate = today.toISOString().split('T')[0];
  const minDate = hundredYearsAgo.toISOString().split('T')[0];

  const [formData, setFormData] = useState({
    firstName: '',
    middleName: '',
    lastName: '',
    gender: '',
    dateOfBirth: '',
    placeOfBirth: '',
    nationality: 'Somali', // Default to Somali
    status: 'ACTIVE',
  });
  const [existingImage, setExistingImage] = useState(null);
  const [existingDocument, setExistingDocument] = useState(null);
  const [imageFile, setImageFile] = useState(null);
  const [documentFile, setDocumentFile] = useState(null);
  const [imagePreview, setImagePreview] = useState(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    // Fetch citizen data to pre-fill form
    const fetchCitizen = async () => {
      if (!nationalId) {
        setError('National ID is required');
        setLoading(false);
        return;
      }

      try {
        setLoading(true);
        setError('');
        const response = await api.get('/api/citizens/get.php', {
          params: { nationalId },
        });

        if (response.data.success && response.data.data) {
          const citizen = response.data.data;
          // Pre-fill form with existing data
          setFormData({
            firstName: citizen.firstName || '',
            middleName: citizen.middleName || '',
            lastName: citizen.lastName || '',
            gender: citizen.gender || '',
            dateOfBirth: citizen.dateOfBirth || '',
            placeOfBirth: citizen.placeOfBirth || '',
            nationality: citizen.nationality || 'Somali',
            status: citizen.status || 'ACTIVE',
          });
          // Set existing file URLs if available
          if (citizen.imageUrl) {
            setExistingImage(citizen.imageUrl);
          }
          if (citizen.documentUrl) {
            setExistingDocument(citizen.documentUrl);
          }
        } else {
          setError('Citizen not found');
        }
      } catch (err) {
        if (err.response?.status === 404) {
          setError('Citizen not found');
        } else {
          setError(
            err.response?.data?.message ||
            err.message ||
            'Failed to load citizen data'
          );
        }
      } finally {
        setLoading(false);
      }
    };

    fetchCitizen();
  }, [nationalId]);

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };

  const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      // Validate file type
      if (!file.type.startsWith('image/')) {
        setError('Please select a valid image file');
        return;
      }
      // Validate file size (5MB)
      if (file.size > 5 * 1024 * 1024) {
        setError('Image file size must be less than 5MB');
        return;
      }
      setImageFile(file);
      setError('');
      
      // Create preview
      const reader = new FileReader();
      reader.onloadend = () => {
        setImagePreview(reader.result);
      };
      reader.readAsDataURL(file);
    }
  };

  const handleDocumentChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      // Validate file size (10MB)
      if (file.size > 10 * 1024 * 1024) {
        setError('Document file size must be less than 10MB');
        return;
      }
      setDocumentFile(file);
      setError('');
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSaving(true);

    try {
      // Validate date of birth range: 100 years ago to today
      if (formData.dateOfBirth > maxDate) {
        setError('Date of birth cannot be in the future');
        setSaving(false);
        return;
      }
      if (formData.dateOfBirth < minDate) {
        setError('Date of birth cannot be more than 100 years ago');
        setSaving(false);
        return;
      }

      // Check if we have files to upload (use FormData) or just data (use JSON)
      const hasFiles = imageFile || documentFile;
      
      if (hasFiles) {
        // Use FormData for file uploads
        const formDataToSend = new FormData();
        formDataToSend.append('nationalId', nationalId);
        formDataToSend.append('firstName', formData.firstName.trim());
        formDataToSend.append('middleName', formData.middleName.trim());
        formDataToSend.append('lastName', formData.lastName.trim());
        formDataToSend.append('gender', formData.gender.toUpperCase());
        formDataToSend.append('dateOfBirth', formData.dateOfBirth);
        formDataToSend.append('placeOfBirth', formData.placeOfBirth.trim());
        formDataToSend.append('nationality', formData.nationality.trim());
        formDataToSend.append('status', formData.status.toUpperCase());
        
        if (imageFile) {
          formDataToSend.append('image', imageFile);
        }
        if (documentFile) {
          formDataToSend.append('document', documentFile);
        }

        const response = await api.post('/api/citizens/update.php', formDataToSend, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        });
        
        if (response.data.success) {
          handleViewCitizenDetails(navigate, nationalId, {
            state: { success: 'Citizen updated successfully' }
          });
        } else {
          setError(response.data.message || 'Failed to update citizen');
        }
      } else {
        // Use JSON for regular updates
        const updateData = {
          nationalId,
          firstName: formData.firstName.trim(),
          middleName: formData.middleName.trim(),
          lastName: formData.lastName.trim(),
          gender: formData.gender.toUpperCase(),
          dateOfBirth: formData.dateOfBirth,
          placeOfBirth: formData.placeOfBirth.trim(),
          nationality: formData.nationality.trim(),
          status: formData.status.toUpperCase(),
        };

        const response = await api.post('/api/citizens/update.php', updateData);

        if (response.data.success) {
          handleViewCitizenDetails(navigate, nationalId, {
            state: { success: 'Citizen updated successfully' }
          });
        } else {
          setError(response.data.message || 'Failed to update citizen');
        }
      }
    } catch (err) {
      setError(
        err.response?.data?.message ||
        err.message ||
        'An error occurred while updating the citizen'
      );
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="max-w-2xl mx-auto">
        <div className="bg-white shadow rounded-lg p-6">
          <div className="text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p className="mt-4 text-gray-600">Loading citizen data...</p>
          </div>
        </div>
      </div>
    );
  }

  if (error && !formData.firstName) {
    return (
      <div className="max-w-2xl mx-auto">
        <div className="bg-white shadow rounded-lg p-6">
          <div className="text-center">
            <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
              <svg
                className="h-6 w-6 text-red-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </div>
            <h2 className="mt-4 text-xl font-bold text-gray-900">{error}</h2>
            <button
              onClick={() => navigate('/citizens')}
              className="mt-4 px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
            >
              Back to Citizens
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div>
        <h1 className="text-3xl font-bold text-gray-900 mb-2">Edit Citizen</h1>
        <p className="text-gray-600">Update citizen information</p>
      </div>

      <div className="bg-white rounded-xl shadow-md p-6 sm:p-8 border border-gray-200">
        {error && (
          <div className="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded-md">
            <div className="flex">
              <div className="flex-shrink-0">
                <svg className="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                </svg>
              </div>
              <div className="ml-3">
                <p className="text-sm text-red-700">{error}</p>
              </div>
            </div>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-6">
          {/* National ID - Read Only */}
          <div>
            <label
              htmlFor="nationalId"
              className="block text-sm font-medium text-gray-700 mb-2"
            >
              National ID <span className="text-gray-500">(Immutable)</span>
            </label>
            <input
              type="text"
              id="nationalId"
              name="nationalId"
              value={nationalId || ''}
              readOnly
              className="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 font-mono text-gray-700 cursor-not-allowed"
            />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label
                htmlFor="firstName"
                className="block text-sm font-medium text-gray-700 mb-2"
              >
                First Name <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                id="firstName"
                name="firstName"
                required
                value={formData.firstName}
                onChange={handleChange}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
              />
            </div>

            <div>
              <label
                htmlFor="middleName"
                className="block text-sm font-medium text-gray-700 mb-2"
              >
                Middle Name
              </label>
              <input
                type="text"
                id="middleName"
                name="middleName"
                value={formData.middleName}
                onChange={handleChange}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
              />
            </div>

            <div>
              <label
                htmlFor="lastName"
                className="block text-sm font-medium text-gray-700 mb-2"
              >
                Last Name <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                id="lastName"
                name="lastName"
                required
                value={formData.lastName}
                onChange={handleChange}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
              />
            </div>

            <div>
              <label
                htmlFor="gender"
                className="block text-sm font-medium text-gray-700 mb-2"
              >
                Gender <span className="text-red-500">*</span>
              </label>
              <select
                id="gender"
                name="gender"
                required
                value={formData.gender}
                onChange={handleChange}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
              >
                <option value="">Select Gender</option>
                <option value="MALE">Male</option>
                <option value="FEMALE">Female</option>
              </select>
            </div>

            <div>
              <label
                htmlFor="dateOfBirth"
                className="block text-sm font-medium text-gray-700 mb-2"
              >
                Date of Birth <span className="text-red-500">*</span>
              </label>
              <input
                type="date"
                id="dateOfBirth"
                name="dateOfBirth"
                required
                value={formData.dateOfBirth}
                onChange={(e) => {
                  const selectedDate = e.target.value;
                  
                  // Validate date range: 100 years ago to today
                  if (selectedDate) {
                    if (selectedDate > maxDate) {
                      setError('Date of birth cannot be in the future');
                      return;
                    }
                    if (selectedDate < minDate) {
                      setError('Date of birth cannot be more than 100 years ago');
                      return;
                    }
                  }
                  setError('');
                  handleChange(e);
                }}
                min={minDate}
                max={maxDate}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
              />
              <p className="text-xs text-gray-500 mt-1">Must be between {minDate} and {maxDate}</p>
            </div>

            <div>
              <label
                htmlFor="placeOfBirth"
                className="block text-sm font-medium text-gray-700 mb-2"
              >
                Place of Birth <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                id="placeOfBirth"
                name="placeOfBirth"
                required
                value={formData.placeOfBirth}
                onChange={handleChange}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
              />
            </div>

            <div>
              <label
                htmlFor="nationality"
                className="block text-sm font-medium text-gray-700 mb-2"
              >
                Nationality
              </label>
              <select
                id="nationality"
                name="nationality"
                value={formData.nationality || 'Somali'}
                onChange={handleChange}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
              >
                <option value="Somali">Somali</option>
                <option value="Ethiopian">Ethiopian</option>
                <option value="Kenyan">Kenyan</option>
                <option value="Djiboutian">Djiboutian</option>
                <option value="Eritrean">Eritrean</option>
                <option value="Yemeni">Yemeni</option>
                <option value="Sudanese">Sudanese</option>
                <option value="Tanzanian">Tanzanian</option>
                <option value="Ugandan">Ugandan</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div>
              <label
                htmlFor="status"
                className="block text-sm font-medium text-gray-700 mb-2"
              >
                Status <span className="text-red-500">*</span>
              </label>
              <select
                id="status"
                name="status"
                required
                value={formData.status}
                onChange={handleChange}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
              >
                <option value="ACTIVE">Active</option>
                <option value="DECEASED">Deceased</option>
              </select>
            </div>
          </div>

          {/* File Upload Section */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-gray-200">
            {/* Image Upload */}
            <div>
              <label
                htmlFor="image"
                className="block text-sm font-medium text-gray-700 mb-2"
              >
                Citizen Photo/Image
              </label>
              <div className="space-y-3">
                {existingImage && !imagePreview && (
                  <div className="mb-3">
                    <p className="text-sm text-gray-600 mb-2">Current Image:</p>
                    <img
                      src={existingImage}
                      alt="Current citizen photo"
                      className="max-w-full h-48 object-contain border border-gray-300 rounded-lg"
                    />
                  </div>
                )}
                <input
                  type="file"
                  id="image"
                  name="image"
                  accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                  onChange={handleImageChange}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 text-sm"
                />
                <p className="text-xs text-gray-500">
                  Accepted formats: JPG, PNG, GIF, WEBP (Max 5MB). Leave empty to keep current image.
                </p>
                {imagePreview && (
                  <div className="mt-3">
                    <p className="text-sm text-gray-600 mb-2">New Image Preview:</p>
                    <img
                      src={imagePreview}
                      alt="Preview"
                      className="max-w-full h-48 object-contain border border-gray-300 rounded-lg"
                    />
                  </div>
                )}
              </div>
            </div>

            {/* Document Upload */}
            <div>
              <label
                htmlFor="document"
                className="block text-sm font-medium text-gray-700 mb-2"
              >
                Supporting Document
              </label>
              <div className="space-y-3">
                {existingDocument && !documentFile && (
                  <div className="mb-3 p-3 bg-gray-50 rounded-lg">
                    <p className="text-sm text-gray-600 mb-2">Current Document:</p>
                    <a
                      href={existingDocument}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-blue-600 hover:text-blue-800 flex items-center gap-2"
                    >
                      <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                      View Current Document
                    </a>
                  </div>
                )}
                <input
                  type="file"
                  id="document"
                  name="document"
                  accept=".pdf,.doc,.docx,image/jpeg,image/jpg,image/png"
                  onChange={handleDocumentChange}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 text-sm"
                />
                <p className="text-xs text-gray-500">
                  Accepted formats: PDF, DOC, DOCX, JPG, PNG (Max 10MB). Leave empty to keep current document.
                </p>
                {documentFile && (
                  <div className="mt-3 p-3 bg-gray-50 rounded-lg">
                    <p className="text-sm text-gray-600">
                      <svg className="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                      New Document: {documentFile.name} ({(documentFile.size / 1024 / 1024).toFixed(2)} MB)
                    </p>
                  </div>
                )}
              </div>
            </div>
          </div>

          <div className="flex space-x-4 pt-4">
            <button
              type="submit"
              disabled={saving}
              className="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition duration-150 shadow-sm hover:shadow-md flex items-center justify-center"
            >
              {saving ? (
                <>
                  <svg className="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Saving...
                </>
              ) : (
                'Update Citizen'
              )}
            </button>
            <button
              type="button"
              onClick={() => handleViewCitizenDetails(navigate, nationalId)}
              disabled={saving}
              className="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default CitizenEdit;

