// Main JavaScript file for TechShop

document.addEventListener("DOMContentLoaded", () => {
  updateCartCountBadge() // Initial cart count update
  initializeToastContainer()

  // Add to cart buttons
  document.querySelectorAll(".add-to-cart-btn").forEach((button) => {
    button.addEventListener("click", function () {
      const productId = this.dataset.productId
      addToCart(productId, 1)
    })
  })

  // Wishlist buttons (basic example, might need more logic for active state)
  document.querySelectorAll(".wishlist-btn").forEach((button) => {
    button.addEventListener("click", function () {
      const productId = this.dataset.productId
      // You'd typically check if it's already in wishlist to toggle add/remove
      addToWishlist(productId)
    })
  })
})

function showToast(message, type = "info") {
  const toastContainer = document.getElementById("toast-container-main")
  if (!toastContainer) {
    console.error("Toast container not found!")
    alert(message) // Fallback
    return
  }

  const toastId = "toast-" + Date.now()
  let bgClass = "bg-info"
  let iconClass = "fa-info-circle"

  if (type === "success") {
    bgClass = "bg-success"
    iconClass = "fa-check-circle"
  } else if (type === "error") {
    bgClass = "bg-danger"
    iconClass = "fa-exclamation-triangle"
  } else if (type === "warning") {
    bgClass = "bg-warning text-dark" // Dark text for warning
    iconClass = "fa-exclamation-circle"
  }

  const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas ${iconClass} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `
  toastContainer.insertAdjacentHTML("beforeend", toastHtml)

  const toastElement = document.getElementById(toastId)
  const toast = new bootstrap.Toast(toastElement, { delay: 5000, autohide: true })
  toast.show()

  toastElement.addEventListener("hidden.bs.toast", () => {
    toastElement.remove()
  })
}

function initializeToastContainer() {
  let container = document.getElementById("toast-container-main")
  if (!container) {
    container = document.createElement("div")
    container.id = "toast-container-main"
    container.className = "toast-container position-fixed top-0 end-0 p-3"
    container.style.zIndex = "1090" // Ensure it's above most elements
    document.body.appendChild(container)
  }
}

function addToCart(productId, quantity = 1) {
  const formData = new FormData()
  formData.append("action", "add")
  formData.append("product_id", productId)
  formData.append("quantity", quantity)

  fetch(`${document.documentElement.dataset.frontendUrl}/api/cart.php`, {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showToast(data.message || "Product added to cart!", "success")

        // Update cart count badge with returned count
        const cartBadge = document.getElementById("cart-count-badge")
        if (cartBadge && data.count !== undefined) {
          cartBadge.textContent = data.count
        } else {
          updateCartCountBadge() // Fallback to separate API call
        }
      } else {
        showToast(data.message || "Error adding product to cart.", "error")
      }
    })
    .catch((error) => {
      console.error("Error adding to cart:", error)
      showToast("Could not connect to server to add product.", "error")
    })
}

function updateCartCountBadge() {
  fetch(`${document.documentElement.dataset.frontendUrl}/api/cart.php?action=count`)
    .then((response) => response.json())
    .then((data) => {
      const cartBadge = document.getElementById("cart-count-badge")
      if (cartBadge) {
        cartBadge.textContent = data.count || 0
      }
    })
    .catch((error) => console.error("Error updating cart count:", error))
}

function addToWishlist(productId) {
  const formData = new FormData()
  formData.append("action", "add")
  formData.append("product_id", productId)

  fetch(`${document.documentElement.dataset.frontendUrl}/api/wishlist.php`, {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showToast(data.message || "Product added to wishlist!", "success")
        // Optionally, update wishlist icon state
      } else {
        showToast(data.message || "Error adding to wishlist.", "error")
      }
    })
    .catch((error) => {
      console.error("Error adding to wishlist:", error)
      showToast("Could not connect to server for wishlist.", "error")
    })
}

// Make FRONTEND_URL available to JS by setting it as a data attribute on the <html> tag
// In your PHP header (e.g., frontend/includes/header.php):
// <html lang="en" data-frontend-url="<?php echo FRONTEND_URL; ?>">
// This is done in the provided header.php

document.addEventListener("DOMContentLoaded", () => {
  // --- Toast Notification Function ---
  function showToast(message, type = "info") {
    // type can be 'success', 'danger', 'warning', 'info'
    const toastContainer = document.querySelector(".toast-container")
    if (!toastContainer) {
      console.error("Toast container not found!")
      return
    }

    const toastId = "toast-" + Math.random().toString(36).substr(2, 9)
    const toastHTML = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `
    toastContainer.insertAdjacentHTML("beforeend", toastHTML)

    const toastElement = document.getElementById(toastId)
    const toastInstance = new bootstrap.Toast(toastElement, { delay: 5000 }) // Auto-hide after 5 seconds
    toastInstance.show()
    toastElement.addEventListener("hidden.bs.toast", () => {
      toastElement.remove()
    })
  }
  window.showToast = showToast // Make it globally accessible if needed from inline scripts

  // --- Update Cart Count Badge ---
  function updateCartCountBadge() {
    const cartBadge = document.getElementById("cart-count-badge")
    if (!cartBadge) return

    fetch(document.documentElement.dataset.frontendUrl + "/api/cart.php?action=get_count", { method: "GET" })
      .then((response) => response.json())
      .then((data) => {
        if (data.success && typeof data.count !== "undefined") {
          cartBadge.textContent = data.count
        } else {
          cartBadge.textContent = "0" // Default or error
        }
      })
      .catch((error) => {
        console.error("Error fetching cart count:", error)
        cartBadge.textContent = "0" // Default on error
      })
  }
  updateCartCountBadge() // Initial call
  window.updateCartCountBadge = updateCartCountBadge // Make global for other scripts

  // --- Add to Cart Functionality (Generic for buttons with class .add-to-cart-btn) ---
  document.querySelectorAll(".add-to-cart-btn").forEach((button) => {
    button.addEventListener("click", function (event) {
      event.preventDefault()
      const productId = this.dataset.productId
      const quantityInput = this.closest("form") ? this.closest("form").querySelector('input[name="quantity"]') : null
      const quantity = quantityInput ? Number.parseInt(quantityInput.value) : 1

      if (!productId) {
        showToast("Product ID not found.", "danger")
        return
      }
      if (isNaN(quantity) || quantity < 1) {
        showToast("Invalid quantity.", "danger")
        return
      }

      const formData = new FormData()
      formData.append("action", "add")
      formData.append("product_id", productId)
      formData.append("quantity", quantity)

      fetch(document.documentElement.dataset.frontendUrl + "/api/cart.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            showToast(data.message || "Product added to cart!", "success")
            updateCartCountBadge()
          } else {
            showToast(data.message || "Error adding product to cart.", "danger")
          }
        })
        .catch((error) => {
          console.error("Add to cart error:", error)
          showToast("Could not add product to cart. Please try again.", "danger")
        })
    })
  })

  // --- Add to Wishlist Functionality ---
  document.querySelectorAll(".wishlist-btn").forEach((button) => {
    button.addEventListener("click", function (event) {
      event.preventDefault()
      if (!document.documentElement.dataset.isLoggedIn) {
        showToast("Please login to add items to your wishlist.", "warning")
        // Optional: redirect to login
        // window.location.href = FRONTEND_URL + '/login.php?redirect=' + encodeURIComponent(window.location.href);
        return
      }

      const productId = this.dataset.productId
      if (!productId) {
        showToast("Product ID not found for wishlist.", "danger")
        return
      }

      fetch(document.documentElement.dataset.frontendUrl + "/api/wishlist.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "add", product_id: productId }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            showToast(data.message || "Product added to wishlist!", "success")
            // Optionally update wishlist icon or count if displayed
          } else {
            showToast(data.message || "Could not add to wishlist.", "danger")
          }
        })
        .catch((error) => {
          console.error("Wishlist error:", error)
          showToast("An error occurred. Please try again.", "danger")
        })
    })
  })

  // --- Newsletter Form Submission (Example) ---
  const newsletterForm = document.getElementById("newsletter-form")
  if (newsletterForm) {
    newsletterForm.addEventListener("submit", (event) => {
      event.preventDefault()
      const emailInput = newsletterForm.querySelector('input[name="newsletter_email"]')
      if (emailInput && emailInput.value) {
        // Basic email validation
        if (!/^\S+@\S+\.\S+$/.test(emailInput.value)) {
          showToast("Please enter a valid email address.", "warning")
          return
        }
        // Replace with actual API call
        // fetch(FRONTEND_URL + '/api/newsletter_subscribe.php', { /* ... */ })
        showToast("Thank you for subscribing!", "success")
        emailInput.value = "" // Clear input
      } else {
        showToast("Please enter your email address.", "warning")
      }
    })
  }

  // --- Auto-dismiss alerts after a few seconds (Bootstrap alerts, not toasts) ---
  const mainAlert = document.querySelector(".main-content .alert")
  if (mainAlert && !mainAlert.closest(".toast")) {
    // Ensure it's not a toast
    setTimeout(() => {
      const alertInstance = bootstrap.Alert.getInstance(mainAlert)
      if (alertInstance) {
        alertInstance.close()
      } else if (mainAlert.classList.contains("show")) {
        // Fallback for alerts not initialized by JS
        new bootstrap.Alert(mainAlert) // Initialize if not already
        setTimeout(() => bootstrap.Alert.getInstance(mainAlert)?.close(), 50) // Then close
      }
    }, 7000) // 7 seconds
  }
})
