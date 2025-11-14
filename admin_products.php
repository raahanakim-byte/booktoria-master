<?php
include 'config.php';
session_start();

$admin_id = $_SESSION['admin_id'];
if(!isset($admin_id)){
    header('location:login.php');
    exit;
}

/* ---------------------------
   ADD / DELETE / UPDATE HANDLERS
   --------------------------- */

/* Add Product */
if(isset($_POST['add_product'])){
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $genre = mysqli_real_escape_string($conn, $_POST['genre']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $price = (int)$_POST['price'];
    $stock = (int)$_POST['stock'];

    $image = $_FILES['image']['name'] ?? '';
    $image_size = $_FILES['image']['size'] ?? 0;
    $image_tmp_name = $_FILES['image']['tmp_name'] ?? '';
    $image_folder = 'uploaded_img/'.$image;

    $check = mysqli_query($conn, "SELECT id FROM `products` WHERE name='$name'") or die('query failed');
    if(mysqli_num_rows($check) > 0){
        $message[] = 'Product name already exists!';
    } else {
        $query = mysqli_query($conn,
            "INSERT INTO `products`(name, author, genre, price, image, location, stock)
            VALUES('$name','$author','$genre','$price','$image','$location','$stock')"
        ) or die('query failed');

        if($query){
            if($image && $image_size <= 2000000 && is_uploaded_file($image_tmp_name)){
                move_uploaded_file($image_tmp_name, $image_folder);
            }
            $message[] = 'Product added successfully!';
        } else {
            $message[] = 'Failed to add product!';
        }
    }
}

/* Delete Product */
if(isset($_GET['delete'])){
    $delete_id = (int)$_GET['delete'];
    $img_query = mysqli_query($conn, "SELECT image FROM `products` WHERE id='$delete_id'") or die('query failed');
    $img = mysqli_fetch_assoc($img_query);
    if(!empty($img['image']) && file_exists('uploaded_img/'.$img['image'])){
        unlink('uploaded_img/'.$img['image']);
    }
    mysqli_query($conn, "DELETE FROM `products` WHERE id='$delete_id'") or die('query failed');
    header('Location: admin_products.php');
    exit;
}

/* Update Product */
if(isset($_POST['update_product'])){
    $id = (int)$_POST['update_p_id'];
    $name = mysqli_real_escape_string($conn, $_POST['update_name']);
    $author = mysqli_real_escape_string($conn, $_POST['update_author']);
    $genre = mysqli_real_escape_string($conn, $_POST['update_genre']);
    $location = mysqli_real_escape_string($conn, $_POST['update_location']);
    $price = (int)$_POST['update_price'];
    $stock = (int)$_POST['update_stock'];

    mysqli_query($conn, "UPDATE `products` SET
        name='$name', author='$author', genre='$genre', location='$location',
        price='$price', stock='$stock'
        WHERE id='$id'") or die('query failed');

    // Update image if uploaded
    if(!empty($_FILES['update_image']['name'])){
        $img = $_FILES['update_image']['name'];
        $img_tmp = $_FILES['update_image']['tmp_name'];
        $img_size = $_FILES['update_image']['size'];
        $img_folder = 'uploaded_img/'.$img;
        $old_img = $_POST['update_old_image'];

        if($img_size <= 2000000 && is_uploaded_file($img_tmp)){
            mysqli_query($conn, "UPDATE `products` SET image='$img' WHERE id='$id'") or die('query failed');
            move_uploaded_file($img_tmp, $img_folder);
            if(!empty($old_img) && file_exists('uploaded_img/'.$old_img)){
                unlink('uploaded_img/'.$old_img);
            }
        } else {
            $message[] = 'Image size too large (max 2MB)';
        }
    }

    header('Location: admin_products.php');
    exit;
}

/* Product Statistics */
$total_products = mysqli_query($conn, "SELECT COUNT(*) as total FROM `products`")->fetch_assoc()['total'];
$low_stock = mysqli_query($conn, "SELECT COUNT(*) as low_stock FROM `products` WHERE stock<5")->fetch_assoc()['low_stock'];
$out_of_stock = mysqli_query($conn, "SELECT COUNT(*) as out_of_stock FROM `products` WHERE stock=0")->fetch_assoc()['out_of_stock'];

/* Pull product list */
$select_products = mysqli_query($conn, "SELECT * FROM `products` ORDER BY id DESC") or die('query failed');

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — Manage Products</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
<link rel="stylesheet" href="css/admin_header.css">
<link rel="stylesheet" href="css/admin_products.css">
</head>
<body>

<?php include 'admin_header.php'; ?>

<main class="main-content">
    <!-- notifications -->
    <?php if(!empty($message)) : ?>
        <?php foreach($message as $msg): ?>
            <div class="admin-message success">
                <span><?php echo htmlspecialchars($msg); ?></span>
                <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <header class="panel-header">
        <h1 class="title">Product Management</h1>
        <div class="panel-actions">
            <button class="btn btn-primary" id="toggle-form-btn"><i class="fas fa-plus"></i> Add Product</button>
            <div class="search-box">
                <input id="product-search" type="search" placeholder="Search products...">
                <i class="fas fa-search"></i>
            </div>
        </div>
    </header>

    <!-- stats -->
    <section class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon total-products">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $total_products; ?></h3>
                <p>Total Products</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon low-stock">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $low_stock; ?></h3>
                <p>Low Stock (&lt;5)</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon out-of-stock">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $out_of_stock; ?></h3>
                <p>Out of Stock</p>
            </div>
        </div>
    </section>

    <!-- add product form (collapsible) -->
    <section id="add-product-section" class="add-product-form" aria-hidden="true">
        <div class="card-header">
            <h3><i class="fas fa-plus-circle"></i> Add New Product</h3>
            <button class="close-btn" id="close-add-form"><i class="fas fa-times"></i></button>
        </div>

        <form action="" method="post" enctype="multipart/form-data" class="form-grid">
            <div class="form-group">
                <label for="name">Product Name *</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Enter product name" required>
            </div>
            
            <div class="form-group">
                <label for="author">Author *</label>
                <input type="text" id="author" name="author" class="form-control" placeholder="Enter author name" required>
            </div>
            
            <div class="form-group">
                <label for="genre">Genre *</label>
                <input type="text" id="genre" name="genre" class="form-control" placeholder="Enter genre" required>
            </div>
            
            <div class="form-group">
                <label for="location">Location *</label>
                <input type="text" id="location" name="location" class="form-control" placeholder="Enter location" required>
            </div>
            
            <div class="form-group">
                <label for="price">Price (Rs.) *</label>
                <input type="number" id="price" name="price" class="form-control" min="0" placeholder="Enter price" required>
            </div>
            
            <div class="form-group">
                <label for="stock">Stock Quantity *</label>
                <input type="number" id="stock" name="stock" class="form-control" min="0" value="1" placeholder="Enter stock" required>
            </div>
            
            <div class="form-group full-width">
                <label for="image">Product Image *</label>
                <div class="file-upload">
                    <input type="file" id="image" name="image" accept="image/jpg, image/jpeg, image/png" required>
                    <div class="file-upload-label">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Choose image file (max 2MB)</span>
                    </div>
                </div>
                <div class="file-preview" id="file-preview"></div>
            </div>
            
            <div class="form-actions full-width">
                <button type="submit" name="add_product" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add Product
                </button>
                <button type="button" id="reset-add" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Reset Form
                </button>
            </div>
        </form>
    </section>

    <!-- Products table - Simplified with only required columns -->
    <section class="product-container">
        <div class="table-box">
            <table id="products-table" class="products-table">
                <thead>
                    <tr>
                        <th style="width:80px">ID</th>
                        <th>Product Name</th>
                        <th>Author</th>
                        <th style="width:120px">Price (Rs.)</th>
                        <th style="width:100px">Stock</th>
                        <th style="width:150px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($select_products) > 0): ?>
                        <?php while($product = mysqli_fetch_assoc($select_products)): 
                            $data = [
                                'id'=>$product['id'],
                                'name'=>$product['name'],
                                'author'=>$product['author'],
                                'genre'=>$product['genre'],
                                'location'=>$product['location'],
                                'price'=>$product['price'],
                                'stock'=>$product['stock'],
                                'image'=>$product['image']
                            ];
                            $data_attr = htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8');
                            $stock_class = $product['stock'] == 0 ? 'out-of-stock' : ($product['stock'] < 5 ? 'low-stock' : 'in-stock');
                            $stock_text = $product['stock'] == 0 ? 'Out of Stock' : ($product['stock'] < 5 ? 'Low Stock' : $product['stock']);
                        ?>
                        <tr data-product="<?php echo $data_attr; ?>">
                            <td class="product-id">#<?php echo $product['id']; ?></td>
                            <td class="product-name"><?php echo htmlspecialchars($product['name']); ?></td>
                            <td class="product-author"><?php echo htmlspecialchars($product['author']); ?></td>
                            <td class="product-price">Rs. <?php echo number_format($product['price']); ?></td>
                            <td class="stock-cell <?php echo $stock_class; ?>">
                                <span class="stock-badge"><?php echo $stock_text; ?></span>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <button class="action edit-btn" title="Edit" data-edit>
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="action delete-btn" title="Delete" data-delete data-id="<?php echo $product['id']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty-box">
                                <div class="empty-state">
                                    <i class="fas fa-box-open"></i>
                                    <h4>No products found</h4>
                                    <p>Get started by adding your first product</p>
                                    <button class="btn btn-primary" id="empty-add-btn">
                                        <i class="fas fa-plus"></i> Add Product
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Edit Modal -->
    <div id="edit-modal" class="modal-overlay" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Update Product</h3>
                <button class="close-btn" id="close-modal"><i class="fas fa-times"></i></button>
            </div>

            <form id="edit-form" action="" method="post" enctype="multipart/form-data" class="form-grid">
                <input type="hidden" name="update_p_id" id="update_p_id" value="">
                <input type="hidden" name="update_old_image" id="update_old_image" value="">

                <div class="form-group">
                    <label for="update_name">Product Name *</label>
                    <input type="text" id="update_name" name="update_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="update_author">Author *</label>
                    <input type="text" id="update_author" name="update_author" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="update_genre">Genre *</label>
                    <input type="text" id="update_genre" name="update_genre" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="update_location">Location *</label>
                    <input type="text" id="update_location" name="update_location" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="update_price">Price (Rs.) *</label>
                    <input type="number" id="update_price" name="update_price" class="form-control" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="update_stock">Stock Quantity *</label>
                    <input type="number" id="update_stock" name="update_stock" class="form-control" min="0" required>
                </div>
                
                <div class="form-group full-width">
                    <label>Current Image</label>
                    <div id="current-image-preview" class="file-preview"></div>
                </div>
                
                <div class="form-group full-width">
                    <label for="update_image">Update Image (Optional)</label>
                    <div class="file-upload">
                        <input type="file" id="update_image" name="update_image" accept="image/jpg, image/jpeg, image/png">
                        <div class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Choose new image (max 2MB)</span>
                        </div>
                    </div>
                    <div class="file-preview" id="update-file-preview"></div>
                </div>
                
                <div class="form-actions full-width">
                    <button type="submit" name="update_product" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <button type="button" id="cancel-edit" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="modal-overlay" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="modal-content delete-modal">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
                <button class="close-btn" id="close-delete-modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="delete-icon">
                    <i class="fas fa-trash"></i>
                </div>
                <h4>Are you sure you want to delete this product?</h4>
                <p>You are about to delete <strong id="delete-product-name"></strong>. This action cannot be undone.</p>
                <div class="delete-details">
                    <div class="detail-item">
                        <span class="label">Product ID:</span>
                        <span class="value" id="delete-product-id"></span>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <a href="#" id="confirm-delete" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Yes, Delete Product
                </a>
                <button type="button" id="cancel-delete" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle add form
    const toggleBtn = document.getElementById('toggle-form-btn');
    const addSection = document.getElementById('add-product-section');
    const closeAdd = document.getElementById('close-add-form');
    const emptyAddBtn = document.getElementById('empty-add-btn');
    
    if(toggleBtn && addSection) {
        toggleBtn.addEventListener('click', () => {
            const isHidden = addSection.getAttribute('aria-hidden') === 'true';
            addSection.setAttribute('aria-hidden', !isHidden);
            addSection.style.display = isHidden ? 'block' : 'none';
        });
    }
    
    if(closeAdd) {
        closeAdd.addEventListener('click', () => {
            addSection.setAttribute('aria-hidden', 'true');
            addSection.style.display = 'none';
        });
    }
    
    if(emptyAddBtn) {
        emptyAddBtn.addEventListener('click', () => {
            addSection.setAttribute('aria-hidden', 'false');
            addSection.style.display = 'block';
        });
    }
    
    // Reset add form
    document.getElementById('reset-add')?.addEventListener('click', () => {
        document.querySelector('#add-product-section form').reset();
        document.getElementById('file-preview').innerHTML = '';
    });

    // Search functionality
    const searchInput = document.getElementById('product-search');
    if(searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.trim().toLowerCase();
            const rows = document.querySelectorAll('#products-table tbody tr');
            rows.forEach(row => {
                if(row.classList.contains('empty-box')) return;
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    }

    // File preview for add form
    const fileInput = document.getElementById('image');
    const filePreview = document.getElementById('file-preview');
    if(fileInput && filePreview) {
        fileInput.addEventListener('change', function() {
            filePreview.innerHTML = '';
            const file = this.files[0];
            if(!file) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Image preview';
                filePreview.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    }

    // Edit modal functionality
    const editModal = document.getElementById('edit-modal');
    const editForm = document.getElementById('edit-form');
    const closeModal = document.getElementById('close-modal');
    const cancelEdit = document.getElementById('cancel-edit');
    const currentPreview = document.getElementById('current-image-preview');
    const updateFilePreview = document.getElementById('update-file-preview');

    // Open edit modal
    document.querySelectorAll('button[data-edit]').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const data = JSON.parse(row.getAttribute('data-product'));
            
            // Fill form with product data
            document.getElementById('update_p_id').value = data.id;
            document.getElementById('update_old_image').value = data.image;
            document.getElementById('update_name').value = data.name;
            document.getElementById('update_author').value = data.author;
            document.getElementById('update_genre').value = data.genre;
            document.getElementById('update_location').value = data.location;
            document.getElementById('update_price').value = data.price;
            document.getElementById('update_stock').value = data.stock;

            // Show current image
            currentPreview.innerHTML = '';
            if(data.image) {
                const img = document.createElement('img');
                img.src = 'uploaded_img/' + data.image;
                img.alt = data.name;
                currentPreview.appendChild(img);
            }

            // Clear update image preview
            updateFilePreview.innerHTML = '';
            document.getElementById('update_image').value = '';

            // Show modal
            editModal.classList.add('active');
            editModal.setAttribute('aria-hidden', 'false');
        });
    });

    // File preview for update image
    const updateImageInput = document.getElementById('update_image');
    if(updateImageInput && updateFilePreview) {
        updateImageInput.addEventListener('change', function() {
            updateFilePreview.innerHTML = '';
            const file = this.files[0];
            if(!file) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'New image preview';
                updateFilePreview.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    }

    // Close edit modal
    function closeEditModal() {
        editModal.classList.remove('active');
        editModal.setAttribute('aria-hidden', 'true');
    }
    if(closeModal) closeModal.addEventListener('click', closeEditModal);
    if(cancelEdit) cancelEdit.addEventListener('click', closeEditModal);
    if(editModal) {
        editModal.addEventListener('click', function(e) {
            if(e.target === this) closeEditModal();
        });
    }

    // Delete modal functionality
    const deleteModal = document.getElementById('delete-modal');
    const closeDeleteModal = document.getElementById('close-delete-modal');
    const cancelDelete = document.getElementById('cancel-delete');
    const confirmDelete = document.getElementById('confirm-delete');
    const deleteProductName = document.getElementById('delete-product-name');
    const deleteProductId = document.getElementById('delete-product-id');

    // Open delete modal
    document.querySelectorAll('button[data-delete]').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            const productName = this.getAttribute('data-name');
            
            deleteProductName.textContent = productName;
            deleteProductId.textContent = '#' + productId;
            confirmDelete.href = 'admin_products.php?delete=' + productId;
            
            deleteModal.classList.add('active');
            deleteModal.setAttribute('aria-hidden', 'false');
        });
    });

    // Close delete modal
    function closeDeleteModalFunc() {
        deleteModal.classList.remove('active');
        deleteModal.setAttribute('aria-hidden', 'true');
    }
    if(closeDeleteModal) closeDeleteModal.addEventListener('click', closeDeleteModalFunc);
    if(cancelDelete) cancelDelete.addEventListener('click', closeDeleteModalFunc);
    if(deleteModal) {
        deleteModal.addEventListener('click', function(e) {
            if(e.target === this) closeDeleteModalFunc();
        });
    }

    // ESC key to close modals
    document.addEventListener('keydown', function(e) {
        if(e.key === 'Escape') {
            if(editModal.classList.contains('active')) closeEditModal();
            if(deleteModal.classList.contains('active')) closeDeleteModalFunc();
            if(addSection.style.display === 'block') {
                addSection.style.display = 'none';
                addSection.setAttribute('aria-hidden', 'true');
            }
        }
    });
});
</script>

</body>
</html>