/**
 * Shared utility for navigating to Citizen Details page
 * Ensures consistent navigation behavior across the application
 */

/**
 * Navigate to citizen details page
 * @param {Function} navigate - React Router navigate function
 * @param {string} nationalId - Citizen's national ID
 * @param {object} options - Optional navigation options (e.g., state for success messages)
 * @returns {boolean} True if navigation occurred, false if validation failed
 */
export const handleViewCitizenDetails = (navigate, nationalId, options = {}) => {
  // Validate nationalId
  if (!nationalId || typeof nationalId !== 'string') {
    console.error('Invalid nationalId provided to handleViewCitizenDetails');
    return false;
  }

  const trimmedNationalId = nationalId.trim();

  // Check if nationalId is empty or "Not available"
  if (!trimmedNationalId || trimmedNationalId === 'Not available' || trimmedNationalId === '') {
    console.error('National ID is empty or not available');
    return false;
  }

  // Navigate to citizen details page using nationalId in URL
  // Options (like state for success messages) can be passed if needed
  navigate(`/citizens/details?nationalId=${trimmedNationalId}`, options);
  return true;
};

