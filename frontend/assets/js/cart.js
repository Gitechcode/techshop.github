// Cart page specific JavaScript

class CartManager {
  constructor() {
    this.init()
  }

  init() {
    this.initializeQuantityControls()
    this.initializeRemoveButtons()
    this.initializeCouponForm()
    this.initializeCheckoutButton()
    this.updateTotals()
  }

  initializeQuantityControls() {
    const quantityInputs = document.querySelectorAll(".quantity-input")
    const quantityBtns = document.querySelectorAll(".quantity-btn")

    quantityInputs.forEach((input) => {
      input.addEventListener("change", (e) => {
        const productId = e.target.getAttribute("data-product-id")
        const quantity = Number.parseInt(e.target.value)
        this.updateQuantity(productId, quantity)
      })
    })

    quantityBtns.forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.preventDefault()
        const action = btn.getAttribute("data-action")
        const productId = btn.getAttribute("data-product-id")
        const input = document.querySelector(`input[data-product-id="${productId}"]`)

        if (input) {
          let quantity = Number.parseInt(input.value)
          const max = Number.parseInt(input.getAttribute("max"))
          const min = Number.parseInt(input.getAttribute("min"))

          if (action === "increase" && quantity < max) {
            quantity++
          } else if (action === "decrease" && quantity > min) {
            quantity--
          }

          input.value = quantity
          this.updateQuantity(productId, quantity)
        }
      })
    })
  }

  initializeRemoveButtons() {
    const removeButtons = document.querySelectorAll(".remove-item-btn")

    removeButtons.forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.preventDefault()
        const productId = btn.getAttribute("data-product-id")
        this.removeItem(productId)
      })
    })
  }

  initializeCouponForm() {
    const couponForm = document.querySelector("#couponForm")
    const couponInput = document.querySelector("#couponCode")
    const applyCouponBtn = document.querySelector("#applyCouponBtn")

    if (couponForm) {
      couponForm.addEventListener("submit", (e) => {
        e.preventDefault()
        const code = couponInput.value.trim()
        if (code) {
          this.applyCoupon(code)
        } else {
          showToast("Please enter a coupon code", "error")
        }
      })
    }

    if (applyCouponBtn) {
      applyCouponBtn.addEventListener("click", (e) => {
        e.preventDefault()
        const code = couponInput.value.trim()
        if (code) {
          this.applyCoupon(code)
        } else {
          showToast("Please enter a coupon code", "error")
        }
      })
    }
  }

  initializeCheckoutButton() {
    const checkoutBtn = document.querySelector("#checkoutBtn")

    if (checkoutBtn) {
      checkoutBtn.addEventListener("click", (e) => {
        // Add any pre-checkout validation here
        this.validateCartBeforeCheckout()
      })
    }
  }

  updateQuantity(productId, quantity) {
    if (quantity < 1) {
      this.removeItem(productId)
      return
    }

    const row = document.querySelector(`tr[data-product-id="${productId}"]`)
    if (row) {
      row.style.opacity = "0.5"
    }

    fetch("/tech-shop/frontend/api/cart.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: "update",
        product_id: productId,
        quantity: quantity,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          this.updateItemTotal(productId, data.item_total)
          this.updateTotals()
          updateCartCount()
          showToast("Cart updated", "success")
        } else {
          showToast(data.message || "Error updating cart", "error")
          // Revert quantity
          const input = document.querySelector(`input[data-product-id="${productId}"]`)
          if (input) {
            input.value = data.old_quantity || 1
          }
        }
      })
      .catch((error) => {
        console.error("Error:", error)
        showToast("Error updating cart", "error")
      })
      .finally(() => {
        if (row) {
          row.style.opacity = "1"
        }
      })
  }

  removeItem(productId) {
    if (!confirm("Are you sure you want to remove this item from your cart?")) {
      return
    }

    const row = document.querySelector(`tr[data-product-id="${productId}"]`)

    fetch("/tech-shop/frontend/api/cart.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: "remove",
        product_id: productId,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          if (row) {
            row.style.transition = "opacity 0.3s ease"
            row.style.opacity = "0"
            setTimeout(() => {
              row.remove()
              this.updateTotals()
              this.checkEmptyCart()
            }, 300)
          }
          updateCartCount()
          showToast("Item removed from cart", "success")
        } else {
          showToast(data.message || "Error removing item", "error")
        }
      })
      .catch((error) => {
        console.error("Error:", error)
        showToast("Error removing item", "error")
      })
  }

  updateItemTotal(productId, newTotal) {
    const totalCell = document.querySelector(`tr[data-product-id="${productId}"] .item-total`)
    if (totalCell) {
      totalCell.textContent = formatPrice(newTotal)
    }
  }

  updateTotals() {
    fetch("/tech-shop/frontend/api/cart.php?action=totals")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          const subtotalElement = document.querySelector("#subtotal")
          const shippingElement = document.querySelector("#shipping")
          const taxElement = document.querySelector("#tax")
          const totalElement = document.querySelector("#total")

          if (subtotalElement) subtotalElement.textContent = formatPrice(data.subtotal)
          if (shippingElement) shippingElement.textContent = data.shipping > 0 ? formatPrice(data.shipping) : "Free"
          if (taxElement) taxElement.textContent = formatPrice(data.tax)
          if (totalElement) totalElement.textContent = formatPrice(data.total)

          // Update free shipping notice
          this.updateShippingNotice(data.subtotal, data.shipping)
        }
      })
  }

  updateShippingNotice(subtotal, shipping) {
    const shippingNotice = document.querySelector("#shippingNotice")
    if (!shippingNotice) return

    const freeShippingThreshold = 100 // This should come from settings

    if (shipping === 0) {
      shippingNotice.innerHTML = `
                <div class="alert alert-success py-2">
                    <small><i class="fas fa-truck me-1"></i>Free shipping applied!</small>
                </div>
            `
    } else {
      const remaining = freeShippingThreshold - subtotal
      shippingNotice.innerHTML = `
                <div class="alert alert-info py-2">
                    <small><i class="fas fa-info-circle me-1"></i>Add ${formatPrice(remaining)} more for free shipping!</small>
                </div>
            `
    }
  }

  applyCoupon(code) {
    const applyCouponBtn = document.querySelector("#applyCouponBtn")
    const originalText = applyCouponBtn.textContent

    applyCouponBtn.disabled = true
    applyCouponBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Applying...'

    fetch("/tech-shop/frontend/api/coupon.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: "apply",
        code: code,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showToast("Coupon applied successfully!", "success")
          this.updateTotals()
          this.displayAppliedCoupon(code, data.discount)
          document.querySelector("#couponCode").value = ""
        } else {
          showToast(data.message || "Invalid coupon code", "error")
        }
      })
      .catch((error) => {
        console.error("Error:", error)
        showToast("Error applying coupon", "error")
      })
      .finally(() => {
        applyCouponBtn.disabled = false
        applyCouponBtn.textContent = originalText
      })
  }

  displayAppliedCoupon(code, discount) {
    const couponContainer = document.querySelector("#appliedCoupons")
    if (!couponContainer) return

    const couponHtml = `
            <div class="applied-coupon d-flex justify-content-between align-items-center p-2 bg-light rounded mb-2">
                <span><i class="fas fa-tag me-2"></i>${code} (-${formatPrice(discount)})</span>
                <button class="btn btn-sm btn-outline-danger" onclick="removeCoupon('${code}')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `

    couponContainer.insertAdjacentHTML("beforeend", couponHtml)
  }

  checkEmptyCart() {
    const cartTable = document.querySelector("#cartTable tbody")
    if (cartTable && cartTable.children.length === 0) {
      // Redirect to empty cart page or show empty state
      location.reload()
    }
  }

  validateCartBeforeCheckout() {
    // Check if cart has items
    const cartItems = document.querySelectorAll("#cartTable tbody tr")
    if (cartItems.length === 0) {
      showToast("Your cart is empty", "error")
      return false
    }

    // Check stock availability
    let hasStockIssues = false
    cartItems.forEach((row) => {
      const quantityInput = row.querySelector(".quantity-input")
      const stock = Number.parseInt(quantityInput.getAttribute("max"))
      const quantity = Number.parseInt(quantityInput.value)

      if (quantity > stock) {
        hasStockIssues = true
      }
    })

    if (hasStockIssues) {
      showToast("Some items in your cart are out of stock", "error")
      return false
    }

    return true
  }

  clearCart() {
    if (!confirm("Are you sure you want to clear your entire cart?")) {
      return
    }

    fetch("/tech-shop/frontend/api/cart.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: "clear",
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showToast("Cart cleared successfully", "success")
          setTimeout(() => {
            location.reload()
          }, 1000)
        } else {
          showToast(data.message || "Error clearing cart", "error")
        }
      })
  }

  saveForLater(productId) {
    fetch("/tech-shop/frontend/api/cart.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: "save_for_later",
        product_id: productId,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showToast("Item saved for later", "success")
          const row = document.querySelector(`tr[data-product-id="${productId}"]`)
          if (row) {
            row.remove()
          }
          this.updateTotals()
          updateCartCount()
        } else {
          showToast(data.message || "Error saving item", "error")
        }
      })
  }
}

// Global functions for cart page
function removeCoupon(code) {
  fetch("/tech-shop/frontend/api/coupon.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      action: "remove",
      code: code,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showToast("Coupon removed", "success")
        document.querySelector(".applied-coupon").remove()
        // Update totals
        if (window.cartManager) {
          window.cartManager.updateTotals()
        }
      }
    })
}

// Initialize cart manager when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  if (document.querySelector(".cart-page")) {
    window.cartManager = new CartManager()
  }
})

// Mock functions to resolve errors. These should be defined elsewhere in the project.
function showToast(message, type) {
  console.log(`Toast: ${message} (type: ${type})`)
}

function updateCartCount() {
  console.log("Cart count updated")
}

function formatPrice(price) {
  return "$" + price.toFixed(2)
}
