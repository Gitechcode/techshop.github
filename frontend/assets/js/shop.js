// Shop page specific JavaScript

class ShopManager {
  constructor() {
    this.currentView = "grid"
    this.currentPage = 1
    this.currentFilters = {}
    this.isLoading = false

    this.init()
  }

  init() {
    this.initializeViewToggle()
    this.initializeFilters()
    this.initializeSorting()
    this.initializePagination()
    this.initializeProductActions()
  }

  initializeViewToggle() {
    const gridViewBtn = document.querySelector("#gridView")
    const listViewBtn = document.querySelector("#listView")
    const productsContainer = document.querySelector("#productsContainer")

    if (gridViewBtn && listViewBtn && productsContainer) {
      gridViewBtn.addEventListener("click", () => {
        this.switchView("grid")
        gridViewBtn.classList.add("active")
        listViewBtn.classList.remove("active")
      })

      listViewBtn.addEventListener("click", () => {
        this.switchView("list")
        listViewBtn.classList.add("active")
        gridViewBtn.classList.remove("active")
      })
    }
  }

  switchView(view) {
    this.currentView = view
    const productsContainer = document.querySelector("#productsContainer")
    const productItems = document.querySelectorAll(".product-item")

    if (view === "list") {
      productsContainer.classList.add("list-view")
      productItems.forEach((item) => {
        item.className = "col-12 product-item mb-3"
      })
    } else {
      productsContainer.classList.remove("list-view")
      productItems.forEach((item) => {
        item.className = "col-lg-4 col-md-6 product-item"
      })
    }
  }

  initializeFilters() {
    const filterForm = document.querySelector("#filterForm")
    const priceRange = document.querySelector("#priceRange")
    const categoryFilter = document.querySelector("#categoryFilter")
    const brandFilter = document.querySelector("#brandFilter")

    if (filterForm) {
      filterForm.addEventListener("submit", (e) => {
        e.preventDefault()
        this.applyFilters()
      })
    }

    // Real-time price range update
    if (priceRange) {
      priceRange.addEventListener(
        "input",
        debounce(() => {
          this.updatePriceDisplay()
        }, 300),
      )
    }

    // Category filter
    if (categoryFilter) {
      categoryFilter.addEventListener("change", () => {
        this.applyFilters()
      })
    }

    // Brand filter
    if (brandFilter) {
      const checkboxes = brandFilter.querySelectorAll('input[type="checkbox"]')
      checkboxes.forEach((checkbox) => {
        checkbox.addEventListener("change", () => {
          this.applyFilters()
        })
      })
    }
  }

  initializeSorting() {
    const sortSelect = document.querySelector("#sortSelect")
    if (sortSelect) {
      sortSelect.addEventListener("change", () => {
        this.applyFilters()
      })
    }
  }

  initializePagination() {
    const paginationLinks = document.querySelectorAll(".pagination .page-link")
    paginationLinks.forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault()
        const page = link.getAttribute("data-page")
        if (page) {
          this.loadPage(Number.parseInt(page))
        }
      })
    })
  }

  initializeProductActions() {
    // Quick view buttons
    const quickViewBtns = document.querySelectorAll(".quick-view-btn")
    quickViewBtns.forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.preventDefault()
        const productId = btn.getAttribute("data-product-id")
        this.showQuickView(productId)
      })
    })

    // Add to cart buttons
    const addToCartBtns = document.querySelectorAll(".add-to-cart-btn")
    addToCartBtns.forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.preventDefault()
        const productId = btn.getAttribute("data-product-id")
        addToCart(productId)
      })
    })

    // Wishlist buttons
    const wishlistBtns = document.querySelectorAll(".wishlist-btn")
    wishlistBtns.forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.preventDefault()
        const productId = btn.getAttribute("data-product-id")
        this.toggleWishlist(productId, btn)
      })
    })

    // Compare buttons
    const compareBtns = document.querySelectorAll(".compare-btn")
    compareBtns.forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.preventDefault()
        const productId = btn.getAttribute("data-product-id")
        this.toggleCompare(productId, btn)
      })
    })
  }

  applyFilters() {
    if (this.isLoading) return

    this.isLoading = true
    this.showLoadingState()

    const formData = new FormData(document.querySelector("#filterForm"))
    const params = new URLSearchParams(formData)

    // Add current page
    params.set("page", this.currentPage)

    // Add AJAX flag
    params.set("ajax", "1")

    fetch(`/tech-shop/frontend/shop.php?${params.toString()}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          this.updateProductsContainer(data.html)
          this.updatePagination(data.pagination)
          this.updateResultsCount(data.total)
        } else {
          showToast("Error loading products", "error")
        }
      })
      .catch((error) => {
        console.error("Error:", error)
        showToast("Error loading products", "error")
      })
      .finally(() => {
        this.isLoading = false
        this.hideLoadingState()
      })
  }

  loadPage(page) {
    this.currentPage = page
    this.applyFilters()

    // Scroll to top of products
    const productsContainer = document.querySelector("#productsContainer")
    if (productsContainer) {
      productsContainer.scrollIntoView({ behavior: "smooth" })
    }
  }

  updateProductsContainer(html) {
    const container = document.querySelector("#productsContainer")
    if (container) {
      container.innerHTML = html
      this.initializeProductActions()

      // Reapply current view
      if (this.currentView === "list") {
        this.switchView("list")
      }
    }
  }

  updatePagination(html) {
    const pagination = document.querySelector(".pagination-container")
    if (pagination) {
      pagination.innerHTML = html
      this.initializePagination()
    }
  }

  updateResultsCount(total) {
    const resultsCount = document.querySelector("#resultsCount")
    if (resultsCount) {
      resultsCount.textContent = `Showing ${total} products`
    }
  }

  showLoadingState() {
    const container = document.querySelector("#productsContainer")
    if (container) {
      container.style.opacity = "0.5"
      container.style.pointerEvents = "none"
    }

    // Show loading spinner
    const loadingSpinner = document.createElement("div")
    loadingSpinner.id = "shopLoadingSpinner"
    loadingSpinner.className = "text-center py-5"
    loadingSpinner.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        `

    if (container) {
      container.appendChild(loadingSpinner)
    }
  }

  hideLoadingState() {
    const container = document.querySelector("#productsContainer")
    if (container) {
      container.style.opacity = "1"
      container.style.pointerEvents = "auto"
    }

    const spinner = document.querySelector("#shopLoadingSpinner")
    if (spinner) {
      spinner.remove()
    }
  }

  updatePriceDisplay() {
    const priceRange = document.querySelector("#priceRange")
    const priceDisplay = document.querySelector("#priceDisplay")

    if (priceRange && priceDisplay) {
      const value = priceRange.value
      priceDisplay.textContent = `$0 - $${value}`
    }
  }

  showQuickView(productId) {
    fetch(`/tech-shop/frontend/api/product.php?id=${productId}&action=quick_view`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          this.displayQuickViewModal(data.product)
        } else {
          showToast("Error loading product details", "error")
        }
      })
  }

  displayQuickViewModal(product) {
    const modalHtml = `
            <div class="modal fade" id="quickViewModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${product.name}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <img src="${product.image || "/placeholder.svg?height=300&width=300"}" 
                                         alt="${product.name}" class="img-fluid rounded">
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <span class="badge bg-secondary">${product.category}</span>
                                    </div>
                                    <p class="text-muted">${product.short_description}</p>
                                    <div class="mb-3">
                                        <span class="h4 text-primary">${formatPrice(product.sale_price || product.price)}</span>
                                        ${product.sale_price ? `<span class="text-muted text-decoration-line-through ms-2">${formatPrice(product.price)}</span>` : ""}
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted">Stock: ${product.stock} available</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Quantity:</label>
                                        <div class="input-group" style="width: 150px;">
                                            <button class="btn btn-outline-secondary" type="button" onclick="decreaseQuantity()">-</button>
                                            <input type="number" class="form-control text-center" id="quickViewQuantity" value="1" min="1" max="${product.stock}">
                                            <button class="btn btn-outline-secondary" type="button" onclick="increaseQuantity()">+</button>
                                        </div>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-primary" onclick="addToCartFromQuickView(${product.id})">
                                            <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                        </button>
                                        <a href="/tech-shop/frontend/product.php?id=${product.id}" class="btn btn-outline-primary">
                                            <i class="fas fa-eye me-2"></i>View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `

    // Remove existing modal
    const existingModal = document.querySelector("#quickViewModal")
    if (existingModal) {
      existingModal.remove()
    }

    // Add new modal
    document.body.insertAdjacentHTML("beforeend", modalHtml)

    // Show modal
    const modal = new bootstrap.Modal(document.querySelector("#quickViewModal"))
    modal.show()
  }

  toggleWishlist(productId, button) {
    const isInWishlist = button.classList.contains("active")

    if (isInWishlist) {
      removeFromWishlist(productId)
      button.classList.remove("active")
      button.querySelector("i").classList.remove("fas")
      button.querySelector("i").classList.add("far")
    } else {
      addToWishlist(productId)
      button.classList.add("active")
      button.querySelector("i").classList.remove("far")
      button.querySelector("i").classList.add("fas")
    }
  }

  toggleCompare(productId, button) {
    const isInCompare = button.classList.contains("active")

    if (isInCompare) {
      removeFromCompare(productId)
      button.classList.remove("active")
    } else {
      addToCompare(productId)
      button.classList.add("active")
    }
  }
}

// Quick view quantity controls
function increaseQuantity() {
  const input = document.querySelector("#quickViewQuantity")
  const max = Number.parseInt(input.getAttribute("max"))
  const current = Number.parseInt(input.value)

  if (current < max) {
    input.value = current + 1
  }
}

function decreaseQuantity() {
  const input = document.querySelector("#quickViewQuantity")
  const min = Number.parseInt(input.getAttribute("min"))
  const current = Number.parseInt(input.value)

  if (current > min) {
    input.value = current - 1
  }
}

// Initialize shop manager when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  if (document.querySelector(".shop-page")) {
    new ShopManager()
  }
})

// Mock functions to resolve undeclared variable errors.  These should be defined elsewhere in the project.
function debounce(func, delay) {
  let timeout
  return function () {
    const args = arguments
    clearTimeout(timeout)
    timeout = setTimeout(() => func.apply(this, args), delay)
  }
}

function addToCart(productId) {
  console.log("addToCart called for product ID: " + productId)
}

function showToast(message, type) {
  console.log("showToast called with message: " + message + " and type: " + type)
}

function formatPrice(price) {
  return "$" + price.toFixed(2)
}

function addToCartFromQuickView(productId) {
  console.log("addToCartFromQuickView called for product ID: " + productId)
}

function removeFromWishlist(productId) {
  console.log("removeFromWishlist called for product ID: " + productId)
}

function addToWishlist(productId) {
  console.log("addToWishlist called for product ID: " + productId)
}

function removeFromCompare(productId) {
  console.log("removeFromCompare called for product ID: " + productId)
}

function addToCompare(productId) {
  console.log("addToCompare called for product ID: " + productId)
}
