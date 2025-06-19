// Product page specific JavaScript

class ProductManager {
  constructor() {
    this.currentImageIndex = 0
    this.images = []
    this.selectedVariants = {}
    this.currentPrice = 0
    this.currentStock = 0

    this.init()
  }

  init() {
    this.initializeImageGallery()
    this.initializeQuantityControls()
    this.initializeVariantSelection()
    this.initializeReviewForm()
    this.initializeTabNavigation()
    this.initializeZoomFeature()
    this.initializeSocialShare()
  }

  initializeImageGallery() {
    const mainImage = document.querySelector("#mainProductImage")
    const thumbnails = document.querySelectorAll(".gallery-thumbnail")

    if (mainImage && thumbnails.length > 0) {
      // Collect all images
      this.images = Array.from(thumbnails).map((thumb) => thumb.getAttribute("data-image"))

      thumbnails.forEach((thumbnail, index) => {
        thumbnail.addEventListener("click", () => {
          this.changeMainImage(index)
          this.updateActiveThumbnail(index)
        })
      })

      // Keyboard navigation
      document.addEventListener("keydown", (e) => {
        if (e.key === "ArrowLeft") {
          this.previousImage()
        } else if (e.key === "ArrowRight") {
          this.nextImage()
        }
      })
    }
  }

  changeMainImage(index) {
    const mainImage = document.querySelector("#mainProductImage")
    if (mainImage && this.images[index]) {
      mainImage.src = this.images[index]
      this.currentImageIndex = index
    }
  }

  updateActiveThumbnail(index) {
    const thumbnails = document.querySelectorAll(".gallery-thumbnail")
    thumbnails.forEach((thumb, i) => {
      if (i === index) {
        thumb.classList.add("active")
      } else {
        thumb.classList.remove("active")
      }
    })
  }

  previousImage() {
    const newIndex = this.currentImageIndex > 0 ? this.currentImageIndex - 1 : this.images.length - 1
    this.changeMainImage(newIndex)
    this.updateActiveThumbnail(newIndex)
  }

  nextImage() {
    const newIndex = this.currentImageIndex < this.images.length - 1 ? this.currentImageIndex + 1 : 0
    this.changeMainImage(newIndex)
    this.updateActiveThumbnail(newIndex)
  }

  initializeQuantityControls() {
    const quantityInput = document.querySelector("#productQuantity")
    const increaseBtn = document.querySelector("#increaseQuantity")
    const decreaseBtn = document.querySelector("#decreaseQuantity")
    const addToCartBtn = document.querySelector("#addToCartBtn")

    if (quantityInput) {
      const maxStock = Number.parseInt(quantityInput.getAttribute("max"))
      this.currentStock = maxStock

      if (increaseBtn) {
        increaseBtn.addEventListener("click", () => {
          const current = Number.parseInt(quantityInput.value)
          if (current < maxStock) {
            quantityInput.value = current + 1
            this.updateAddToCartButton()
          }
        })
      }

      if (decreaseBtn) {
        decreaseBtn.addEventListener("click", () => {
          const current = Number.parseInt(quantityInput.value)
          if (current > 1) {
            quantityInput.value = current - 1
            this.updateAddToCartButton()
          }
        })
      }

      quantityInput.addEventListener("change", () => {
        const value = Number.parseInt(quantityInput.value)
        if (value < 1) {
          quantityInput.value = 1
        } else if (value > maxStock) {
          quantityInput.value = maxStock
          this.showToast(`Only ${maxStock} items available`, "warning")
        }
        this.updateAddToCartButton()
      })
    }

    if (addToCartBtn) {
      addToCartBtn.addEventListener("click", () => {
        this.addToCart()
      })
    }
  }

  initializeVariantSelection() {
    const variantSelects = document.querySelectorAll(".variant-select")

    variantSelects.forEach((select) => {
      select.addEventListener("change", () => {
        const variantType = select.getAttribute("data-variant")
        const variantValue = select.value

        this.selectedVariants[variantType] = variantValue
        this.updateProductDetails()
      })
    })
  }

  updateProductDetails() {
    // This would typically make an AJAX call to get variant-specific details
    const productId = document.querySelector("[data-product-id]").getAttribute("data-product-id")

    fetch(`/tech-shop/frontend/api/product.php?id=${productId}&variants=${JSON.stringify(this.selectedVariants)}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          this.updatePrice(data.price, data.sale_price)
          this.updateStock(data.stock)
          this.updateImages(data.images)
        }
      })
  }

  updatePrice(price, salePrice) {
    const priceElement = document.querySelector("#productPrice")
    const originalPriceElement = document.querySelector("#originalPrice")

    if (priceElement) {
      const displayPrice = salePrice || price
      priceElement.textContent = this.formatPrice(displayPrice)
      this.currentPrice = displayPrice
    }

    if (originalPriceElement) {
      if (salePrice) {
        originalPriceElement.textContent = this.formatPrice(price)
        originalPriceElement.style.display = "inline"
      } else {
        originalPriceElement.style.display = "none"
      }
    }
  }

  updateStock(stock) {
    this.currentStock = stock
    const stockElement = document.querySelector("#stockStatus")
    const quantityInput = document.querySelector("#productQuantity")

    if (stockElement) {
      if (stock > 0) {
        stockElement.textContent = `${stock} in stock`
        stockElement.className = "text-success"
      } else {
        stockElement.textContent = "Out of stock"
        stockElement.className = "text-danger"
      }
    }

    if (quantityInput) {
      quantityInput.setAttribute("max", stock)
      if (Number.parseInt(quantityInput.value) > stock) {
        quantityInput.value = Math.max(1, stock)
      }
    }

    this.updateAddToCartButton()
  }

  updateImages(images) {
    if (images && images.length > 0) {
      this.images = images
      this.changeMainImage(0)
      this.updateThumbnails(images)
    }
  }

  updateThumbnails(images) {
    const thumbnailContainer = document.querySelector("#thumbnailContainer")
    if (thumbnailContainer) {
      thumbnailContainer.innerHTML = ""
      images.forEach((image, index) => {
        const thumbnail = document.createElement("img")
        thumbnail.src = image
        thumbnail.className = `gallery-thumbnail ${index === 0 ? "active" : ""}`
        thumbnail.setAttribute("data-image", image)
        thumbnail.addEventListener("click", () => {
          this.changeMainImage(index)
          this.updateActiveThumbnail(index)
        })
        thumbnailContainer.appendChild(thumbnail)
      })
    }
  }

  updateAddToCartButton() {
    const addToCartBtn = document.querySelector("#addToCartBtn")
    if (addToCartBtn) {
      if (this.currentStock > 0) {
        addToCartBtn.disabled = false
        addToCartBtn.textContent = "Add to Cart"
      } else {
        addToCartBtn.disabled = true
        addToCartBtn.textContent = "Out of Stock"
      }
    }
  }

  addToCart() {
    const productId = document.querySelector("[data-product-id]").getAttribute("data-product-id")
    const quantity = Number.parseInt(document.querySelector("#productQuantity").value)

    if (this.currentStock < quantity) {
      this.showToast("Not enough stock available", "error")
      return
    }

    const addToCartBtn = document.querySelector("#addToCartBtn")
    const originalText = addToCartBtn.textContent

    addToCartBtn.disabled = true
    addToCartBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...'

    // Include selected variants in the request
    const cartData = {
      action: "add",
      product_id: productId,
      quantity: quantity,
      variants: this.selectedVariants,
    }

    fetch("/tech-shop/frontend/api/cart.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(cartData),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          this.showToast("Product added to cart!", "success")
          this.updateCartCount()
          this.showAddedToCartModal()
        } else {
          this.showToast(data.message || "Error adding product to cart", "error")
        }
      })
      .catch((error) => {
        console.error("Error:", error)
        this.showToast("Error adding product to cart", "error")
      })
      .finally(() => {
        addToCartBtn.disabled = false
        addToCartBtn.textContent = originalText
      })
  }

  showAddedToCartModal() {
    const modalHtml = `
            <div class="modal fade" id="addedToCartModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Added to Cart
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <p>Product has been added to your cart successfully!</p>
                            <div class="d-grid gap-2">
                                <a href="/tech-shop/frontend/cart.php" class="btn btn-primary">
                                    <i class="fas fa-shopping-cart me-2"></i>View Cart
                                </a>
                                <a href="/tech-shop/frontend/checkout.php" class="btn btn-success">
                                    <i class="fas fa-credit-card me-2"></i>Checkout Now
                                </a>
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                    Continue Shopping
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `

    // Remove existing modal
    const existingModal = document.querySelector("#addedToCartModal")
    if (existingModal) {
      existingModal.remove()
    }

    // Add new modal
    document.body.insertAdjacentHTML("beforeend", modalHtml)

    // Show modal
    const modal = new bootstrap.Modal(document.querySelector("#addedToCartModal"))
    modal.show()

    // Auto-hide after 3 seconds
    setTimeout(() => {
      modal.hide()
    }, 3000)
  }

  initializeReviewForm() {
    const reviewForm = document.querySelector("#reviewForm")
    const ratingStars = document.querySelectorAll(".rating-star")
    let selectedRating = 0

    if (ratingStars.length > 0) {
      ratingStars.forEach((star, index) => {
        star.addEventListener("click", () => {
          selectedRating = index + 1
          this.updateStarDisplay(selectedRating)
        })

        star.addEventListener("mouseover", () => {
          this.updateStarDisplay(index + 1)
        })
      })

      const ratingContainer = document.querySelector(".rating-stars")
      if (ratingContainer) {
        ratingContainer.addEventListener("mouseleave", () => {
          this.updateStarDisplay(selectedRating)
        })
      }
    }

    if (reviewForm) {
      reviewForm.addEventListener("submit", (e) => {
        e.preventDefault()
        this.submitReview(selectedRating)
      })
    }
  }

  updateStarDisplay(rating) {
    const stars = document.querySelectorAll(".rating-star")
    stars.forEach((star, index) => {
      if (index < rating) {
        star.classList.add("active")
      } else {
        star.classList.remove("active")
      }
    })
  }

  submitReview(rating) {
    const productId = document.querySelector("[data-product-id]").getAttribute("data-product-id")
    const title = document.querySelector("#reviewTitle").value
    const comment = document.querySelector("#reviewComment").value

    if (rating === 0) {
      this.showToast("Please select a rating", "error")
      return
    }

    if (!title.trim() || !comment.trim()) {
      this.showToast("Please fill in all fields", "error")
      return
    }

    const submitBtn = document.querySelector("#submitReviewBtn")
    const originalText = submitBtn.textContent

    submitBtn.disabled = true
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...'

    fetch("/tech-shop/frontend/api/review.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: "submit",
        product_id: productId,
        rating: rating,
        title: title,
        comment: comment,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          this.showToast("Review submitted successfully!", "success")
          document.querySelector("#reviewForm").reset()
          this.updateStarDisplay(0)
          // Optionally reload reviews section
          this.loadReviews()
        } else {
          this.showToast(data.message || "Error submitting review", "error")
        }
      })
      .catch((error) => {
        console.error("Error:", error)
        this.showToast("Error submitting review", "error")
      })
      .finally(() => {
        submitBtn.disabled = false
        submitBtn.textContent = originalText
      })
  }

  loadReviews() {
    const productId = document.querySelector("[data-product-id]").getAttribute("data-product-id")
    const reviewsContainer = document.querySelector("#reviewsContainer")

    if (reviewsContainer) {
      fetch(`/tech-shop/frontend/api/review.php?product_id=${productId}&action=list`)
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            reviewsContainer.innerHTML = data.html
          }
        })
    }
  }

  initializeTabNavigation() {
    const tabButtons = document.querySelectorAll(".product-tab-btn")
    const tabContents = document.querySelectorAll(".product-tab-content")

    tabButtons.forEach((button) => {
      button.addEventListener("click", (e) => {
        e.preventDefault()
        const targetTab = button.getAttribute("data-tab")

        // Update active button
        tabButtons.forEach((btn) => btn.classList.remove("active"))
        button.classList.add("active")

        // Update active content
        tabContents.forEach((content) => {
          if (content.id === targetTab) {
            content.classList.add("active")
          } else {
            content.classList.remove("active")
          }
        })
      })
    })
  }

  initializeZoomFeature() {
    const mainImage = document.querySelector("#mainProductImage")
    const zoomContainer = document.querySelector("#imageZoomContainer")

    if (mainImage && zoomContainer) {
      mainImage.addEventListener("mousemove", (e) => {
        this.handleImageZoom(e)
      })

      mainImage.addEventListener("mouseleave", () => {
        this.hideImageZoom()
      })
    }
  }

  handleImageZoom(e) {
    const image = e.target
    const rect = image.getBoundingClientRect()
    const x = e.clientX - rect.left
    const y = e.clientY - rect.top

    const xPercent = (x / rect.width) * 100
    const yPercent = (y / rect.height) * 100

    const zoomContainer = document.querySelector("#imageZoomContainer")
    if (zoomContainer) {
      zoomContainer.style.display = "block"
      zoomContainer.style.backgroundImage = `url(${image.src})`
      zoomContainer.style.backgroundPosition = `${xPercent}% ${yPercent}%`
    }
  }

  hideImageZoom() {
    const zoomContainer = document.querySelector("#imageZoomContainer")
    if (zoomContainer) {
      zoomContainer.style.display = "none"
    }
  }

  initializeSocialShare() {
    const shareButtons = document.querySelectorAll(".social-share-btn")

    shareButtons.forEach((button) => {
      button.addEventListener("click", (e) => {
        e.preventDefault()
        const platform = button.getAttribute("data-platform")
        this.shareProduct(platform)
      })
    })
  }

  shareProduct(platform) {
    const productName = document.querySelector("h1").textContent
    const productUrl = window.location.href
    const productImage = document.querySelector("#mainProductImage").src

    let shareUrl = ""

    switch (platform) {
      case "facebook":
        shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(productUrl)}`
        break
      case "twitter":
        shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(productName)}&url=${encodeURIComponent(productUrl)}`
        break
      case "pinterest":
        shareUrl = `https://pinterest.com/pin/create/button/?url=${encodeURIComponent(productUrl)}&media=${encodeURIComponent(productImage)}&description=${encodeURIComponent(productName)}`
        break
      case "whatsapp":
        shareUrl = `https://wa.me/?text=${encodeURIComponent(productName + " " + productUrl)}`
        break
    }

    if (shareUrl) {
      window.open(shareUrl, "_blank", "width=600,height=400")
    }
  }

  // Helper functions (can be moved to a separate utility file)
  showToast(message, type = "success") {
    const toastContainer = document.getElementById("toastContainer")
    if (!toastContainer) {
      const container = document.createElement("div")
      container.id = "toastContainer"
      container.className = "toast-container position-fixed top-0 end-0 p-3"
      document.body.appendChild(container)
    }

    const toastId = "toast-" + Date.now()
    const toastMarkup = `
            <div class="toast align-items-center text-white bg-${type} border-0" id="${toastId}" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `

    document.getElementById("toastContainer").insertAdjacentHTML("beforeend", toastMarkup)
    const toastElement = document.getElementById(toastId)
    const toast = new bootstrap.Toast(toastElement)
    toast.show()

    toastElement.addEventListener("hidden.bs.toast", () => {
      toastElement.remove()
    })
  }

  formatPrice(price) {
    return "$" + price.toFixed(2)
  }

  updateCartCount() {
    // Implement your cart count update logic here
    // This is a placeholder
    console.log("Cart count updated")
  }
}

// Initialize product manager when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  if (document.querySelector(".product-page")) {
    new ProductManager()
  }
})
