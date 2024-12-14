// sweetalert.js
const SweetAlert = {
    // Simple Alert
    alert: function (title = "Alert", text = "This is a simple alert", icon = "info") {
      Swal.fire({
        title: title,
        text: text,
        icon: icon,
        confirmButtonText: "OK",
      });
    },
  
    // Success Alert
    success: function (title = "Success", text = "Operation successful!") {
      Swal.fire({
        title: title,
        text: text,
        icon: "success",
        confirmButtonText: "Great!",
      });
    },
  
    // Error Alert
    error: function (title = "Error", text = "Something went wrong!") {
      Swal.fire({
        title: title,
        text: text,
        icon: "error",
        confirmButtonText: "Try Again",
      });
    },
  
    // Warning Alert
    warning: function (title = "Warning", text = "Please be careful!") {
      Swal.fire({
        title: title,
        text: text,
        icon: "warning",
        confirmButtonText: "Understood",
      });
    },
  
    // Confirmation Alert
    confirm: function (title = "Are you sure?", text = "You won't be able to revert this!", icon = "warning", confirmButtonText = "Yes, do it!", cancelButtonText = "Cancel", callback) {
      Swal.fire({
        title: title,
        text: text,
        icon: icon,
        showCancelButton: true,
        confirmButtonText: confirmButtonText,
        cancelButtonText: cancelButtonText,
      }).then((result) => {
        if (result.isConfirmed) {
          if (typeof callback === "function") {
            callback(true);
          }
        } else {
          if (typeof callback === "function") {
            callback(false);
          }
        }
      });
    },
  
    // Custom Alert
    custom: function (options) {
      Swal.fire(options);
    },
  
    // Loading Alert
    loading: function (title = "Loading...", text = "Please wait") {
      Swal.fire({
        title: title,
        text: text,
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        },
      });
    },
  
    // Close Alert
    close: function () {
      Swal.close();
    },
  };
  
  // Example Usage
  // SweetAlert.alert("Hello", "This is a custom alert", "info");
  // SweetAlert.success("Success!", "Your data has been saved.");
  // SweetAlert.error("Oops!", "Something went wrong.");
  // SweetAlert.warning("Warning!", "This action is irreversible.");
  // SweetAlert.confirm("Delete item?", "This action cannot be undone.", "warning", "Yes, delete it", "Cancel", (confirmed) => {
  //   if (confirmed) {
  //     console.log("Item deleted");
  //   } else {
  //     console.log("Action canceled");
  //   }
  // });
  // SweetAlert.loading();
  // setTimeout(() => SweetAlert.close(), 2000);
  
  