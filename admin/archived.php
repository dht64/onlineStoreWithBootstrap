<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/bs_ecommerce/core/init.php';
if(!is_logged_in()){
	login_error_redirect();
}
include 'includes/head.php';
include 'includes/navigation.php';

//Delete product
if(isset($_GET['delete'])){
  $id = sanitize($_GET['delete']);
  $productResults = $db->query("SELECT * FROM products WHERE id = '$id'");
  $product = mysqli_fetch_assoc($productResults); 
  $image_url = $_SERVER['DOCUMENT_ROOT'].$product['image'];
  unlink($image_url);
  $db->query("DELETE FROM products WHERE id = '$id'");
  $_SESSION['success_flash'] = 'Product has been deleted!';
  header('Location: archived.php');
}

//Restore product
if(isset($_GET['restore'])){
  $id = sanitize($_GET['restore']);
  $db->query("UPDATE products SET deleted = 0 WHERE id = '$id'");
  $_SESSION['success_flash'] = 'Product has been restored!';
  header('Location: archived.php');
}

$dbPath = '';
if(isset($_GET['add']) || isset($_GET['edit'])){
$brandQuery = $db->query("SELECT * FROM brand ORDER BY brand");
$parentQuery = $db->query("SELECT * FROM categories WHERE parent = 0 ORDER BY category");
$title = ((isset($_POST['title']) && $_POST['title'] != '')?sanitize($_POST['title']):'');
$brand = ((isset($_POST['brand']) && !empty($_POST['brand']))?sanitize($_POST['brand']):'');
$parent = ((isset($_POST['parent']) && !empty($_POST['parent']))?sanitize($_POST['parent']):'');
$category = ((isset($_POST['child']) && !empty($_POST['child']))?sanitize($_POST['child']):''); 
$price = ((isset($_POST['price']) && $_POST['price'] != '')?sanitize($_POST['price']):''); 
$list_price = ((isset($_POST['list_price']) && $_POST['list_price'] != '')?sanitize($_POST['list_price']):''); 
$description = ((isset($_POST['description']) && $_POST['description'] != '')?sanitize($_POST['description']):''); 
$sizes = ((isset($_POST['sizes']) && $_POST['sizes'] != '')?sanitize($_POST['sizes']):''); 
$saved_image = '';
  if(isset($_GET['edit'])){
	$edit_id = (int)$_GET['edit'];
	$productResults = $db->query("SELECT * FROM products WHERE id = '$edit_id'");
	$product = mysqli_fetch_assoc($productResults); 
	if(isset($_GET['delete_image'])){
	  $image_url = $_SERVER['DOCUMENT_ROOT'].$product['image'];
	  unlink($image_url);
	  $db->query("UPDATE products SET image = '' WHERE id = '$edit_id'");
	  header('Location: products.php?edit='.$edit_id);
	}
	$category = ((isset($_POST['child']) && $_POST['child'] != '')?sanitize($_POST['child']):$product['categories']);
	$title = ((isset($_POST['title']) && $_POST['title'] != '')?sanitize($_POST['title']):$product['title']);
	$brand = ((isset($_POST['brand']) && $_POST['brand'] != '')?sanitize($_POST['brand']):$product['brand']);
	$parentQ = $db->query("SELECT * FROM categories WHERE id = '$category'");
	$parentResult = mysqli_fetch_assoc($parentQ);
	$parent = ((isset($_POST['parent']) && !empty($_POST['parent']))?sanitize($_POST['parent']):$parentResult['parent']);
	$price = ((isset($_POST['price']) && $_POST['price'] != '')?sanitize($_POST['price']):$product['price']);
	$list_price = ((isset($_POST['list_price']) && $_POST['list_price'] != '')?sanitize($_POST['list_price']):$product['list_price']);
	$description = ((isset($_POST['description']) && $_POST['description'] != '')?sanitize($_POST['description']):$product['description']);
	$sizes = ((isset($_POST['sizes']) && $_POST['sizes'] != '')?sanitize($_POST['sizes']):$product['sizes']);
	$saved_image = (($product['image'] != '')?$product['image']:'');
	$dbPath = $saved_image;
  }
    if(!empty($sizes)){
	$sizeString = sanitize($sizes);
	$sizeString = rtrim($sizeString,',');
	$sizeArray = explode(',',$sizeString);
	$sArray = array();
	$qArray = array();
	foreach($sizeArray as $ss){
	  $s = explode(':', $ss);
	  $sArray[] = $s[0];
	  $qArray[] = $s[1];
	}
  } else {$sizeArray = array();}
if($_POST){
/*  $title = sanitize($_POST['title']);
  $brand = sanitize($_POST['brand']); 
  $categories = sanitize($_POST['child']);
  $price = sanitize($_POST['price']);
  $list_price = sanitize($_POST['list_price']);
  $sizes = sanitize($_POST['sizes']);
  $description = sanitize($_POST['description']);*/
  $errors = array();
  $require = array('title', 'brand', 'price', 'parent', 'child', 'sizes');
  foreach($require as $field){
	if($_POST[$field] == ''){
	  $errors[] = 'All Fields with Asterisk are required.';
	  break;
	}
  }
  if($_FILES['photo']['error'] == 0){
	$photo = $_FILES['photo'];
	$name = $photo['name'];
	$nameArray = explode('.', $name);
	$fileName = $nameArray[0];
	$fileExt = $nameArray[1];
	$mime = explode('/', $photo['type']);
	$mimeType = $mime[0];
	$mimeExt = $mime[1];
	$tmpLoc = $photo['tmp_name'];
	$fileSize = $photo['size'];
	$allowed = array('png','jpg','jpeg','gif');
	$uploadName = md5(microtime()).'.'.$fileExt;
	$uploadPath = BASEURL.'/images/products/'.$uploadName;
	$dbPath = '/bs_ecommerce/images/products/'.$uploadName;
	if($mimeType != 'image'){
	  $errors[] = 'The file must be an image.'; 
	}
	if(!in_array($fileExt, $allowed)){
	  $errors[] = 'The file extension must be a png, jpg, jpeg or gif.';
	}
	if($fileSize > 15000000){
	 $errors[] = 'The file size must be under 15MB.';
	}
	if($fileExt != $mimeExt && ($mimeExt == 'jpeg' && $fileExt != 'jpg')){
	  $errors[] = 'File extension does not match the file.';
	}
  }
  if(!empty($errors)){
	echo display_errors($errors);
  } else {
	  //Upload file and insert into database
	  if($_FILES['photo']['error'] == 0){
	  move_uploaded_file($tmpLoc,$uploadPath);
	  }
	  $insertSql = "INSERT INTO products (`title`,`price`,`list_price`,`brand`,`categories`,`sizes`,`image`,`description`) 
		VALUES ('$title','$price','$list_price','$brand','$category','$sizeString','$dbPath','$description')";
		if(isset($_GET['edit'])){
		  $insertSql = "UPDATE products SET title = '$title', price = '$price', list_price = '$list_price',
			brand = '$brand', categories = '$category', sizes = '$sizeString', image = '$dbPath', description = '$description'
			WHERE id='$edit_id'";
		}
	  $db->query($insertSql);
	  header('Location: products.php');
  }
}
?>  	
  <h2 class="text-center"><?=((isset($_GET['edit']))?'Edit':'Add A New');?> Product</h2><hr>
  <form action="products.php?<?=((isset($_GET['edit']))?'edit='.$edit_id:'add=1') ?>" method="POST" enctype="multipart/form-data">
    <div class="form-group col-md-3">
	  <label for="title">Title*:</label>
	  <input type="text" name="title" class="form-control" id="title" value="<?=$title;?>" required></input>
	</div>
	<div class="form-group col-md-3">
	  <label for="brand">Brand*:</label>
	  <select class="form-control" id="brand" name="brand">
	    <option value=""<?=(($brand == '')?' selected':''); ?>></option>
		<?php while($b = mysqli_fetch_assoc($brandQuery)): ?>
		  <option value="<?=$b['id'];?>"<?=(($brand == $b['id'])?' selected':''); ?>><?=$b['brand'];?></option>
		<?php endwhile; ?>
	  </select>
	</div>
	<div class="form-group col-md-3">
	  <label for="parent">Parent Category*:</label>
	  <select class="form-control" id="parent" name="parent">
	    <option value=""<?=(($parent == '')?' selected':'');?>></option>
		<?php while($p = mysqli_fetch_assoc($parentQuery)): ?>
		  <option value="<?=$p['id'];?>"<?=(($parent == $p['id'])?' selected':'');?>><?=$p['category'];?></option>		
		<?php endwhile; ?>
	  </select>
	</div>
	<div class="form-group col-md-3">
	  <label for="child">Child Category*:</label>
	  <select id="child" name="child" class="form-control">
	  </select>
	</div>
	<div class="form-group col-md-3">
	  <label for="price">Price*:</label>
	  <input type="text" id="price" name="price" class="form-control" value="<?=$price;?>">
	</div>
	<div class="form-group col-md-3">
	  <label for="list_price">List Price:</label>
	  <input type="text" id="list_price" name="list_price" class="form-control" value="<?=$list_price;?>">
	</div>
	<div class="form-group col-md-3">
	  <label>Quantity & Sizes*:</label>
	  <button class="btn btn-default form-control" onclick="jQuery('#sizesModal').modal('toggle'); return false;">Quantity & Sizes</button>
	</div>
	<div class="form-group col-md-3">
	  <label for="sizes">Sizes & Qty Preview</label>
	  <input type="text" name="sizes" id="sizes" class="form-control" value="<?=$sizes;?>" readonly>
	</div>
	<div class="form-group col-md-6">
	  <?php if($saved_image != ''): ?>
	    <div class="saved-image">
		  <img src="<?=$saved_image;?>" alt="saved image" /><br>
		  <a href="products.php?delete_image=1&edit=<?=$edit_id;?>" class="text-danger">Delete Image</a>  
		</div>
	  <?php endif; ?>
	  <label for="photo">Product Photo:</label>
	  <input type="file" name="photo" id="photo">

	</div>
	<div class="form-group col-md-6">
	  <label for="description">Description</label>
	  <textarea id="description" name="description" class="form-control" rows="6"><?=$description;?></textarea>
	</div>
	<div class="form-group pull-right" style="padding:0 15px">
	  <a href="products.php" class="btn btn-default">Cancel</a>
	  <input type="submit" value="<?=((isset($_GET['edit']))?'Edit':'Add');?> Product" class="btn btn-success">
	</div>
  </form>
  <!-- Modal -->
<div class="modal fade" id="sizesModal" tabindex="-1" role="dialog" aria-labelledby="sizesModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="sizesModalLabel">Size & Quantity</h4>
      </div>
      <div class="modal-body">
	    <div class="container-fluid">
        <?php for($i=1;$i <= 12;$i++): ?>
		  <div class="form-group col-md-4">
			<label for="size<?=$i;?>">Size</label>
			<input type="text" name="size<?=$i;?>" id="size<?=$i;?>" value="<?=((!empty($sArray[$i-1]))?$sArray[$i-1]:'');?>" class="form-control">
		  </div>
		  <div class="form-group col-md-2">
			<label for="qty<?=$i;?>">Quantity</label>
			<input type="number" name="qty<?=$i;?>" id="qty<?=$i;?>" value="<?=((!empty($qArray[$i-1]))?$qArray[$i-1]:'');?>" min="0" class="form-control">
		  </div>
		<?php endfor; ?>
		</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="updateSizes();jQuery('#sizesModal').modal('toggle');return false;">Save changes</button>
      </div>
    </div>
  </div>
</div>
  <div class="clearfix"></div>
<?php }else{

$sql = "SELECT * FROM products WHERE deleted = 1";
$presults = $db->query($sql);
if(isset($_GET['featured'])){
  $id = (int)$_GET['id'];
  $featured = (int)$_GET['featured'];
  $featuredsql = "UPDATE products SET featured = '$featured' WHERE id = '$id'";
  $db->query($featuredsql);
  header('Location: archived.php');
}
?>
<h2 class="text-center">Archived</h2>
<table class="table table-bordered table-condensed table-striped">
  <thead>
	<th></th>
	<th>Product</th>
	<th>Price</th>
	<th>Category</th>
	<th>Featured</th>
	<th>Sold</th>
  </thead>
  <tbody>
	<?php while($product = mysqli_fetch_assoc($presults)): 
	  $childId = $product['categories'];
	  $catSql = "SELECT * FROM categories WHERE id = '$childId'";
	  $result = $db->query($catSql);
	  $child = mysqli_fetch_assoc($result);
	  $parentId = $child['parent'];
	  $pSql = "SELECT * FROM categories WHERE id = '$parentId'";
	  $presult = $db->query($pSql);
	  $parent = mysqli_fetch_assoc($presult);
	  $category = $parent['category'].'-'.$child['category'];
	?>
	  <tr>
		<td>
		  <a href="archived.php?edit=<?=$product['id'];?>" class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="top" title="Edit product"><span class="glyphicon glyphicon-pencil"></span></a>
		  <a href="archived.php?delete=<?=$product['id'];?>" class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="top" title="Delete product"><span class="glyphicon glyphicon-trash"></span></a>
		  <a href="archived.php?restore=<?=$product['id'];?>" class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="top" title="Restore product"><span class="glyphicon glyphicon-refresh"></span></a>
		</td>
		<td><?=$product['title'];?></td>
		<td><?=money($product['price']);?></td>
		<td><?=$category;?></td>
		<td><a href="archived.php?featured=<?=(($product['featured']==0)?'1':'0');?>&id=<?=$product['id'];?>" 
			class="btn btn-xs btn-default"><span class="glyphicon glyphicon-<?=(($product['featured']==1)?'minus':'plus');?>" 
			data-toggle="tooltip" data-placement="top" title="<?=(($product['featured']==1)?'Remove as featured':'Add as featured');?> product"></span>	
			</a>&nbsp <?=(($product['featured']==1)?'Featured Product':'');?></td>
		<td>0</td>
	  </tr>
	<?php endwhile; ?>
  </tbody>
</table>

<?php }
include 'includes/footer.php';
?>
<script>
  jQuery('document').ready(function(){
	get_child_options('<?=$category;?>'); 
	$('[data-toggle="tooltip"]').tooltip();
  });
</script>