# SmartDialog Quick Reference

## One-Liners for Common Tasks

### Alerts
```javascript
SmartDialog.info('Your message', 'Title');           // ℹ️  Info dialog
SmartDialog.success('Success!', 'Done');             // ✓ Success dialog
SmartDialog.warning('Be careful!', 'Warning');       // ⚠️  Warning dialog
SmartDialog.error('Something went wrong', 'Error');  // ✕ Error dialog
```

### Confirmation
```javascript
SmartDialog.confirm('Are you sure?', 'Confirm',
  function(ok) { if(ok) { /* do stuff */ } }
);
```

### User Input
```javascript
SmartDialog.prompt('Name?', '', 'Enter',
  function(value) { console.log(value); }
);
```

### Custom Dialog
```javascript
SmartDialog.show('HTML content here', {
  type: 'info',
  title: 'My Dialog',
  buttons: [
    { label: 'Cancel', className: 'secondary' },
    { label: 'OK', className: 'primary', callback: function() { } }
  ]
});
```

### Close Dialog Programmatically
```javascript
SmartDialog.close();
```

### Check if Dialog is Open
```javascript
if (SmartDialog.isOpen()) { /* dialog is showing */ }
```

---

## Common Migration Examples

### Example 1: Form Validation
**Before:**
```javascript
function validateForm() {
  if (!form.email) {
    alert('Email is required');
    return false;
  }
}
```

**After:**
```javascript
function validateForm() {
  if (!form.email) {
    SmartDialog.error('Email is required', 'Validation Error');
    return false;
  }
}
```

### Example 2: Confirmation Before Delete
**Before:**
```javascript
function deleteRecord(id) {
  if (confirm('Delete this record?')) {
    $.post('delete.php', {id: id}, function(result) {
      alert('Record deleted');
    });
  }
}
```

**After:**
```javascript
function deleteRecord(id) {
  SmartDialog.confirm('Delete this record?', 'Confirm Delete',
    function(ok) {
      if (ok) {
        $.post('delete.php', {id: id}, function(result) {
          SmartDialog.success('Record deleted', 'Success');
        });
      }
    }
  );
}
```

### Example 3: AJAX Error Handling
**Before:**
```javascript
$('#btnSave').click(function() {
  $.post('save.php', form.serialize())
    .done(function() { alert('Saved'); })
    .fail(function(err) { alert('Error: ' + err.statusText); });
});
```

**After:**
```javascript
$('#btnSave').click(function() {
  $.post('save.php', form.serialize())
    .done(function() { SmartDialog.success('Saved', 'Success'); })
    .fail(function(err) { SmartDialog.error('Error: ' + err.statusText, 'Save Failed'); });
});
```

### Example 4: Multi-Step Confirmation
**Before:**
```javascript
if (confirm('Save changes?')) {
  if (confirm('Send notification?')) {
    alert('Everything done!');
  }
}
```

**After:**
```javascript
SmartDialog.confirm('Save changes?', 'Confirm',
  function(ok) {
    if (ok) {
      SmartDialog.confirm('Send notification?', 'Notify',
        function(ok2) {
          if (ok2) {
            SmartDialog.success('Everything done!', 'Complete');
          }
        }
      );
    }
  }
);
```

---

## Dialog Types & Icons

| Type | Icon | Usage |
|------|------|-------|
| `info` | 📋 | General information, instructions |
| `success` | ✓ | Operation completed successfully |
| `warning` | ⚠️ | Caution, risky operation, data loss warning |
| `error` | ✕ | Operation failed, error occurred |
| `confirm` | ❓ | User confirmation needed |
| `question` | ❓ | User input/question |

---

## Advanced Options

### Auto-Hide Dialog
```javascript
SmartDialog.show('This will close automatically', {
  type: 'info',
  title: 'Notice',
  autoClose: 3000  // 3 seconds
});
```

### Dialog Without Backdrop (No Outside Click to Close)
```javascript
SmartDialog.show('Message', {
  type: 'warning',
  title: 'Important',
  backdrop: false,
  keyboard: false
});
```

### No Animation (Instant)
```javascript
SmartDialog.show('Message', {
  type: 'info',
  title: 'Dialog',
  animation: false
});
```

### Custom Width
```javascript
SmartDialog.show('Message', {
  type: 'info',
  title: 'Large Dialog',
  width: '700px'
});
```

---

## Styling Custom Dialogs

### Add Custom HTML
```javascript
var html = '<p>Normal text</p>' +
           '<strong>Bold text</strong>' +
           '<ul><li>List item</li></ul>';

SmartDialog.show(html, {
  type: 'info',
  title: 'Custom Content'
});
```

### Form Dialog
```javascript
var formHtml = '<form>' +
  '<input type="text" placeholder="Name" class="smart-dialog-input">' +
  '<input type="email" placeholder="Email" class="smart-dialog-input">' +
  '</form>';

SmartDialog.show(formHtml, {
  type: 'question',
  title: 'Enter Details',
  buttons: [
    { label: 'Cancel', className: 'secondary' },
    { label: 'Submit', className: 'primary', callback: function() {
      var name = $('input[placeholder="Name"]').val();
      var email = $('input[placeholder="Email"]').val();
      console.log(name, email);
    }}
  ]
});
```

---

## Tips & Tricks

### 1. Chaining Operations
```javascript
SmartDialog.info('Processing...', 'Please Wait');
setTimeout(function() {
  SmartDialog.close();
  SmartDialog.success('Done!', 'Complete');
}, 2000);
```

### 2. Getting User Response
```javascript
var userChoice = false;
SmartDialog.confirm('Continue?', 'Confirm',
  function(ok) {
    userChoice = ok;
    performNextAction(userChoice);
  }
);
```

### 3. Show Dialog Only Once
```javascript
if (!sessionStorage.getItem('tutorial_shown')) {
  SmartDialog.info(
    'This is a one-time tutorial message.',
    'Tutorial'
  );
  sessionStorage.setItem('tutorial_shown', '1');
}
```

### 4. Disable Close Button
```javascript
// Remove close button by creating custom header
SmartDialog.show('Important message - must read', {
  type: 'warning',
  title: 'Critical',
  keyboard: false,  // No ESC to close
  backdrop: false,  // No outside click to close
  buttons: [
    { label: 'I Understand', className: 'primary' }
  ]
});
```

### 5. Dynamic Content
```javascript
function showUserData(userId) {
  $.get('getUserInfo.php?id=' + userId, function(data) {
    SmartDialog.show(
      '<strong>Name:</strong> ' + data.name + '<br>' +
      '<strong>Email:</strong> ' + data.email,
      { type: 'info', title: 'User Profile' }
    );
  });
}
```

---

## Performance Notes

### Do:
- ✅ Reuse dialog instances when possible
- ✅ Close dialogs when done (`SmartDialog.close()`)
- ✅ Use appropriate dialog types (helps UX)
- ✅ Keep messages concise

### Don't:
- ❌ Create dialogs in loops
- ❌ Leave auto-close dialogs to distract users
- ❌ Use dialogs for every notification (consider toast alternatives)
- ❌ Add massive content to dialogs

---

## CSS Classes (Advanced)

All dialogs have these classes:
- `.smart-dialog` - Main dialog container
- `.smart-dialog-header` - Title area
- `.smart-dialog-body` - Content area
- `.smart-dialog-footer` - Buttons area
- `.smart-dialog-info`, `.smart-dialog-success`, etc. - Type-specific styling
- `.smart-dialog-btn-primary`, `.smart-dialog-btn-secondary` - Button styles

Override styles:
```css
.smart-dialog {
  color: #your-color;
}
.smart-dialog-btn-primary {
  background: #your-gradient;
}
```

---

## Keyboard Shortcuts

When a dialog is open:
- **ESC** - Close dialog (if `keyboard: true`)
- **ENTER** - Click primary button (if `keyboard: true`)
- **TAB** - Cycle through buttons
- **SPACE** - Click focused button

---

## Troubleshooting

### Dialog doesn't appear
```javascript
// Check if jQuery is loaded
console.log(typeof jQuery);  // should be 'function'

// Check if SmartDialog is loaded
console.log(typeof SmartDialog);  // should be 'object'

// Check for JavaScript errors
// Open browser Dev Tools → Console tab
```

### Styling is wrong
```javascript
// Clear browser cache (Ctrl+F5)
// Check if CSS file is linked in header.inc
// Check for CSS conflicts with other stylesheets
// Use browser Inspector to debug
```

### Dialog closes too quickly
```javascript
// Remove autoClose setting, or increase the timeout:
SmartDialog.show('Message', {
  autoClose: 5000  // 5 seconds instead of 3
});
```

---

## File Locations

- **JavaScript**: `/javascripts/SmartDialog.js`
- **CSS**: `/css/smart-dialog.css`
- **Usage Examples**: This file or UI_MODERNIZATION_GUIDE.md

---

Need more help? Check the full guide: **UI_MODERNIZATION_GUIDE.md**
