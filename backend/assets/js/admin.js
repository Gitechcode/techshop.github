import { Chart } from "@/components/ui/chart"
// Admin Panel JavaScript

// Global variables
const currentModal = null

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  initializeAdmin()
})

// Initialize admin functionality
function initializeAdmin() {
  initializeDataTables()
  initializeTooltips()
  initializeConfirmDialogs()
  initializeFileUploads()
  initializeFormValidation()
  initializeCharts()
}

// Initialize DataTables
function initializeDataTables() {
  if (typeof $.fn.DataTable) {
    $(".data-table").DataTable({
      responsive: true,
      pageLength: 25,
      order: [[0, "desc"]],
      language: {
        search: "Search:",
        lengthMenu: "Show _MENU_ entries",
        info: "Showing _START_ to _END_ of _TOTAL_ entries",
        paginate: {
          first: "First",
          last: "Last",
          next: "Next",
          previous: "Previous",
        },
      },
    })
  }
}

// Initialize tooltips
function initializeTooltips() {
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipTriggerList.map((tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl))
}

// Initialize confirm dialogs
function initializeConfirmDialogs() {
  document.addEventListener("click", (e) => {
    if (e.target.hasAttribute("data-confirm")) {
      const message = e.target.getAttribute("data-confirm")
      if (!confirm(message)) {
        e.preventDefault()
        return false
      }
    }
  })
}

// Initialize file uploads
function initializeFileUploads() {
  const fileInputs = document.querySelectorAll('input[type="file"]')

  fileInputs.forEach((input) => {
    input.addEventListener("change", function () {
      const file = this.files[0]
      if (file) {
        // Validate file size (5MB limit)
        if (file.size > 5 * 1024 * 1024) {
          showAlert("File size must be less than 5MB", "error")
          this.value = ""
          return
        }

        // Validate file type for images
        if (this.accept && this.accept.includes("image/*")) {
          const allowedTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif", "image/webp"]
          if (!allowedTypes.includes(file.type)) {
            showAlert("Please select a valid image file (JPEG, PNG, GIF, WebP)", "error")
            this.value = ""
            return
          }
        }

        // Show preview for images
        if (file.type.startsWith("image/")) {
          showImagePreview(this, file)
        }
      }
    })
  })
}

// Show image preview
function showImagePreview(input, file) {
  const reader = new FileReader()
  reader.onload = (e) => {
    let preview = input.parentNode.querySelector(".image-preview")
    if (!preview) {
      preview = document.createElement("div")
      preview.className = "image-preview mt-2"
      input.parentNode.appendChild(preview)
    }

    preview.innerHTML = `
            <img src="${e.target.result}" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
            <button type="button" class="btn btn-sm btn-danger ms-2" onclick="removeImagePreview(this)">
                <i class="fas fa-times"></i>
            </button>
        `
  }
  reader.readAsDataURL(file)
}

// Remove image preview
function removeImagePreview(button) {
  const preview = button.parentNode
  const input = preview.parentNode.querySelector('input[type="file"]')
  input.value = ""
  preview.remove()
}

// Initialize form validation
function initializeFormValidation() {
  const forms = document.querySelectorAll(".needs-validation")

  forms.forEach((form) => {
    form.addEventListener("submit", (e) => {
      if (!form.checkValidity()) {
        e.preventDefault()
        e.stopPropagation()
      }
      form.classList.add("was-validated")
    })
  })
}

// Initialize charts
function initializeCharts() {
  // This will be called if Chart.js is loaded
  if (typeof Chart !== "undefined") {
    Chart.defaults.global.defaultFontFamily = "Nunito"
    Chart.defaults.global.defaultFontColor = "#858796"
  }
}

// Show alert message
function showAlert(message, type = "info") {
  const alertContainer = getOrCreateAlertContainer()

  const alertId = "alert-" + Date.now()
  const alertClass = type === "error" ? "danger" : type
  const iconClass = getAlertIcon(type)

  const alertHtml = `
        <div id="${alertId}" class="alert alert-${alertClass} alert-dismissible fade show" role="alert">
            <i class="fas fa-${iconClass} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `

  alertContainer.insertAdjacentHTML("beforeend", alertHtml)

  // Auto-hide after 5 seconds
  setTimeout(() => {
    const alertElement = document.getElementById(alertId)
    if (alertElement) {
      const alert = new bootstrap.Alert(alertElement)
      alert.close()
    }
  }, 5000)
}

// Get or create alert container
function getOrCreateAlertContainer() {
  let container = document.querySelector(".alert-container")
  if (!container) {
    container = document.createElement("div")
    container.className = "alert-container"

    const mainContent = document.querySelector("#content .container-fluid")
    if (mainContent) {
      mainContent.insertBefore(container, mainContent.firstChild)
    } else {
      document.body.appendChild(container)
    }
  }
  return container
}

// Get alert icon based on type
function getAlertIcon(type) {
  switch (type) {
    case "success":
      return "check-circle"
    case "error":
    case "danger":
      return "exclamation-triangle"
    case "warning":
      return "exclamation-triangle"
    case "info":
      return "info-circle"
    default:
      return "info-circle"
  }
}

// Show loading state
function showLoading(element) {
  if (typeof element === "string") {
    element = document.querySelector(element)
  }

  if (element) {
    element.classList.add("loading")
    element.style.pointerEvents = "none"
  }
}

// Hide loading state
function hideLoading(element) {
  if (typeof element === "string") {
    element = document.querySelector(element)
  }

  if (element) {
    element.classList.remove("loading")
    element.style.pointerEvents = "auto"
  }
}

// AJAX helper function
function makeAjaxRequest(url, options = {}) {
  const defaultOptions = {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  }

  const finalOptions = { ...defaultOptions, ...options }

  return fetch(url, finalOptions)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return response.json()
    })
    .catch((error) => {
      console.error("AJAX request failed:", error)
      showAlert("An error occurred while processing your request", "error")
      throw error
    })
}

// Format currency
function formatCurrency(amount) {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(amount)
}

// Format date
function formatDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  })
}

// Format date and time
function formatDateTime(dateString) {
  const date = new Date(dateString)
  return date.toLocaleString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  })
}

// Debounce function
function debounce(func, wait) {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

// Throttle function
function throttle(func, limit) {
  let inThrottle
  return function () {
    const args = arguments

    if (!inThrottle) {
      func.apply(this, args)
      inThrottle = true
      setTimeout(() => (inThrottle = false), limit)
    }
  }
}

// Export data to CSV
function exportToCSV(data, filename) {
  const csvContent = convertToCSV(data)
  const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" })
  const link = document.createElement("a")

  if (link.download !== undefined) {
    const url = URL.createObjectURL(blob)
    link.setAttribute("href", url)
    link.setAttribute("download", filename)
    link.style.visibility = "hidden"
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  }
}

// Convert data to CSV format
function convertToCSV(data) {
  if (!data || data.length === 0) return ""

  const headers = Object.keys(data[0])
  const csvHeaders = headers.join(",")

  const csvRows = data.map((row) => {
    return headers
      .map((header) => {
        const value = row[header]
        return typeof value === "string" && value.includes(",") ? `"${value}"` : value
      })
      .join(",")
  })

  return [csvHeaders, ...csvRows].join("\n")
}

// Print functionality
function printElement(elementId) {
  const element = document.getElementById(elementId)
  if (!element) {
    showAlert("Element not found for printing", "error")
    return
  }

  const printWindow = window.open("", "_blank")
  printWindow.document.write(`
        <html>
            <head>
                <title>Print</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body { font-family: Arial, sans-serif; }
                    @media print {
                        .no-print { display: none !important; }
                    }
                </style>
            </head>
            <body>
                ${element.innerHTML}
            </body>
        </html>
    `)
  printWindow.document.close()
  printWindow.print()
}

// Bulk actions handler
function handleBulkAction(action, selectedIds) {
  if (selectedIds.length === 0) {
    showAlert("Please select at least one item", "warning")
    return
  }

  const confirmMessage = `Are you sure you want to ${action} ${selectedIds.length} item(s)?`
  if (!confirm(confirmMessage)) {
    return
  }

  // Show loading
  showLoading("body")

  // Make AJAX request
  makeAjaxRequest("api/bulk-actions.php", {
    method: "POST",
    body: JSON.stringify({
      action: action,
      ids: selectedIds,
    }),
  })
    .then((response) => {
      if (response.success) {
        showAlert(response.message, "success")
        // Reload page or update UI
        setTimeout(() => location.reload(), 1000)
      } else {
        showAlert(response.message || "An error occurred", "error")
      }
    })
    .finally(() => {
      hideLoading("body")
    })
}

// Select all checkboxes
function toggleSelectAll(masterCheckbox) {
  const checkboxes = document.querySelectorAll(".item-checkbox")
  checkboxes.forEach((checkbox) => {
    checkbox.checked = masterCheckbox.checked
  })
  updateBulkActionButtons()
}

// Update bulk action buttons
function updateBulkActionButtons() {
  const selectedCheckboxes = document.querySelectorAll(".item-checkbox:checked")
  const bulkActionButtons = document.querySelectorAll(".bulk-action-btn")

  if (selectedCheckboxes.length > 0) {
    bulkActionButtons.forEach((btn) => (btn.disabled = false))
  } else {
    bulkActionButtons.forEach((btn) => (btn.disabled = true))
  }
}

// Initialize bulk actions
function initializeBulkActions() {
  // Master checkbox
  const masterCheckbox = document.querySelector("#selectAll")
  if (masterCheckbox) {
    masterCheckbox.addEventListener("change", function () {
      toggleSelectAll(this)
    })
  }

  // Individual checkboxes
  const itemCheckboxes = document.querySelectorAll(".item-checkbox")
  itemCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", updateBulkActionButtons)
  })

  // Initial state
  updateBulkActionButtons()
}

// Auto-save functionality
function initializeAutoSave(formSelector, saveUrl, interval = 30000) {
  const form = document.querySelector(formSelector)
  if (!form) return

  let autoSaveTimer
  let hasChanges = false

  // Track changes
  form.addEventListener("input", () => {
    hasChanges = true
    clearTimeout(autoSaveTimer)
    autoSaveTimer = setTimeout(autoSave, interval)
  })

  function autoSave() {
    if (!hasChanges) return

    const formData = new FormData(form)

    makeAjaxRequest(saveUrl, {
      method: "POST",
      body: formData,
    })
      .then((response) => {
        if (response.success) {
          hasChanges = false
          showAlert("Auto-saved", "success")
        }
      })
      .catch((error) => {
        console.error("Auto-save failed:", error)
      })
  }

  // Save before leaving page
  window.addEventListener("beforeunload", (e) => {
    if (hasChanges) {
      e.preventDefault()
      e.returnValue = "You have unsaved changes. Are you sure you want to leave?"
    }
  })
}

// Initialize when DOM is ready
$(document).ready(() => {
  initializeBulkActions()
})
