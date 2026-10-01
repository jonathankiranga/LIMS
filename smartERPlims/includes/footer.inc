<?php

echo '</div></body>';

?> 
    
<script>
    
    // Sales Photos Button Handler - Safe Version
if (document.getElementById("getsalesphotos")) {
    
    document.getElementById("getsalesphotos").addEventListener("click", async function(event) {
  event.preventDefault(); // Prevent the form submission

  try {
    const response = await $.ajax({
      url: 'salespictures.php',
      method: "GET",
      // No data to send, since this is a GET request
    });

    // When the request is complete and successful, load the response into the DIV
    document.getElementById("imageContainer").innerHTML = response;
   // Show the modal
    document.getElementById("salesPhotosModal").style.display = "block";
  // Add click event listeners to the loaded images
    addImageClickListeners();
  } catch (error) {
    console.error('Error fetching sales pictures:', error);
  }
});
   
}

// Add click listeners to image items
function addImageClickListeners() {
  var images = document.querySelectorAll('.image-item');
  var selectedImages = JSON.parse(document.getElementById('selectedImages').value || '[]');

  images.forEach(function(image) {
    image.addEventListener('click', function() {
      var imageSrc = this.querySelector('img').src;
      this.classList.toggle('selected');

      if (this.classList.contains('selected')) {
        if (!selectedImages.includes(imageSrc)) {
          selectedImages.push(imageSrc);
        }
      } else {
        var imageIndex = selectedImages.indexOf(imageSrc);
        if (imageIndex !== -1) {
          selectedImages.splice(imageIndex, 1);
        }
      }

      // Update the hidden input fields
      document.getElementById('selectedRef').value = document.getElementById("salesid").value;
      document.getElementById('selectedImages').value = JSON.stringify(selectedImages);
    });
  });
}

// Close the modal without clearing the selections
function closeModal(clearSelection) {
  var selectedImages = JSON.parse(document.getElementById('selectedImages').value || '[]');
  var referenceValue = document.getElementById('selectedRef').value;

  if (selectedImages.length >= 1) {
    var message = {
      ref: referenceValue,
      images: selectedImages
    };

    // Dispatch a custom event
    var event = new CustomEvent('imageSelectionComplete', { detail: message });
    document.dispatchEvent(event);
  }

  // Hide the modal
  modal.style.display = "none";

  // Clear the selections if specified
  if (clearSelection) {
    document.getElementById('selectedRef').value = '';
    document.getElementById('selectedImages').value = '[]';
  }
}

// Accept the modal and dispatch the selected images
function acceptModal() {
  closeModal(false);
}


   // Sales Photos Button Handler - Safe Version
if (document.getElementById("getsalesphotos")) {
 
// Get the modal and close elements
var modal = document.getElementById("salesPhotosModal");
var span = document.getElementsByClassName("close")[0];
var accept = document.getElementsByClassName("accept")[0];

// Close the modal when the user clicks on <span> (x) without clearing selections
span.onclick = function() {
  closeModal(false);
};

// Close the modal when the user clicks accept without clearing selections
accept.onclick = function() {
  acceptModal();
};

// Close the modal when the user clicks outside of it without clearing selections
window.onclick = function(event) {
  if (event.target == modal) {
    closeModal(false);
  }
};

}
// Add event listener for the custom event
document.addEventListener('imageSelectionComplete', function(event) {
  console.log('Image selection complete:', event.detail);
  // Handle the selected images and reference (e.g., update UI or send data to server)
});

// Ensure the value in 'selectedImages' is parsed and stringified correctly
document.addEventListener('DOMContentLoaded', function() {
    
  var selectedImagesField = document.getElementById('selectedImages');
  if (!selectedImagesField) return; // Guard against null
  
  if (selectedImagesField.value === '') {
    selectedImagesField.value = '[]';
  } else {
    try {
      JSON.parse(selectedImagesField.value);
    } catch (e) {
      selectedImagesField.value = '[]';
    }
  }
});

</script>
<?php
  echo '</html>';
  if($_SESSION['ManualNumber']==1){
?>
   <script type="text/javascript">
   $(':input').removeAttr('readonly'); 
   </script>
<?php 
   }
?>