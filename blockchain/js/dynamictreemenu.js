// Fetch menu items via AJAX and generate the menu

// Declare quillInstances globally
let quillInstances = {};
 
function fetchAndGenerateMenu(roleId) {
    $.ajax({
        url: 'ajax/load_menu.php', // Replace with your endpoint returning the menu JSON
        method: 'POST',
        dataType: 'json',
        data: { role_id: roleId },  // Pass the user role ID to the server
        success: function (menuItems) {
            generateMenu(menuItems);
        },
        error: function () {
            console.error('Failed to load menu items.');
            $('#treeMenu').html('<li class="nav-item">Failed to load menu.</li>');
        }
    });
}

function generateMenu(menuItems) {
    const menuContainer = document.getElementById("treeMenu");
  
    menuItems.forEach(item => {
        const listItem = document.createElement("li");
        listItem.classList.add("nav-item");
        
        if (item.submenus) {
            
            const mainLink = document.createElement("a");
            mainLink.href = "#";
            mainLink.classList.add("nav-link");
            mainLink.innerHTML = `<i class="${item.icon}"></i> ${item.title}`;
            mainLink.setAttribute("data-toggle", "collapse");
            mainLink.setAttribute("data-target", `#submenu-${item.title.replace(/\s+/g, '-')}`);
            listItem.appendChild(mainLink);

            const submenuContainer = document.createElement("ul");
            submenuContainer.classList.add("submenu", "collapse");
            submenuContainer.id = `submenu-${item.title.replace(/\s+/g, '-')}`;
 
            item.submenus.forEach(subItem => {
                const subItemElement = document.createElement("li");
                const subItemLink = document.createElement("a");
                subItemLink.href = subItem.url || "#"; // Use URL if provided, else default to "#"
                subItemLink.classList.add("nav-link");
                subItemLink.dataset.page = subItem.url;
                subItemLink.dataset.title = `${item.title} > ${subItem.title}`; // For breadcrumb
                subItemLink.innerText = subItem.title;
                subItemElement.appendChild(subItemLink);
                submenuContainer.appendChild(subItemElement);
            });

            listItem.appendChild(submenuContainer);
        } else {
            const link = document.createElement("a");
            link.href = item.url || "#";
            link.classList.add("nav-link");
            link.dataset.page = item.url;
            link.dataset.title = item.title; // For breadcrumb
            
            link.innerHTML = `<i class="${item.icon}"></i> ${item.title}`;
            listItem.appendChild(link);
        }

        menuContainer.appendChild(listItem);
    });
    
     // Add modal as the last menu item
    const modalItem2 = document.createElement("li");
    const modalLink2 = document.createElement("a");
   // modalLink2.href = "#";
    modalLink2.classList.add("nav-link");
    modalLink2.innerHTML = `<i class="fas fa-signature"></i> Update Signature`;  
    modalLink2.setAttribute("data-toggle", "modal");
    modalLink2.setAttribute("data-target", "#signatureModal");
    modalItem2.appendChild(modalLink2);
    menuContainer.appendChild(modalItem2);
        
    modalLink2.addEventListener('click', function(event) {
        event.preventDefault();  // Prevent default behavior (jumping to #)
        const myModal = new bootstrap.Modal(document.getElementById('signatureModal'));
        myModal.show();  // Show the modal
    });
    

    
}

// Function to update breadcrumbs


function updateBreadcrumbs(pathArray) {
    const breadcrumbContainer = document.getElementById("breadcrumbs");
    breadcrumbContainer.innerHTML = "";

    // Always add Home first
    const homeItem = document.createElement("li");
    homeItem.classList.add("breadcrumb-item");
    homeItem.innerHTML = `<a href="homepage.php"><i class="fas fa-home"></i> Home</a>`;
    breadcrumbContainer.appendChild(homeItem);

    // pathArray is now an array of {title, url}
    pathArray.forEach((part, index) => {
        const breadcrumbItem = document.createElement("li");
        breadcrumbItem.classList.add("breadcrumb-item");

        if (index === pathArray.length - 1) {
            breadcrumbItem.innerText = part.title; // last item, plain text
        } else {
            breadcrumbItem.innerHTML = `<a href="${part.url}">${part.title}</a>`;
        }

        breadcrumbContainer.appendChild(breadcrumbItem);
    });
}

// Toggle submenu visibility
$('.tree-menu').on('click', 'a[data-toggle="collapse"]', function (e) {
    e.preventDefault();
    $(this).next('.submenu').slideToggle();
});

// AJAX content loading and breadcrumb update



$('.tree-menu').on('click', '.nav-link', function (e) {
    const page = $(this).data('page');   // e.g. "monthly.php"
    const title = $(this).data('title'); // e.g. "Reports > Monthly"
    if (!page) {
        return;
    }
    e.preventDefault();

    const parts = String(title || '').split(" > ").filter(Boolean);
    const pathArray = parts.map((part, index) => {
        return {
            title: part,
            url: index === parts.length - 1 ? page : '#'
        };
    });

    if (page) {
        $('#page-content-closable').empty();
        $('#page-content-closable').off();
        $('#page-content-closable').html(`
            <button class="close-btn" onclick="closePopout()">×</button>
            <p>Loading...</p>
        `);

        // Load page content via AJAX
        $('#page-content-closable').load(page, function (response, status) {
            if (status === "error") {
                console.error("Error loading page:", response);
                $('#page-content-closable').html(`
                    <button class="close-btn" onclick="closePopout()">×</button>
                    <p>Error loading page. Please try again later.</p>
                `);
            } else {
                if (!$('#page-content-closable .close-btn').length) {
                    $('#page-content-closable').prepend('<button class="close-btn" onclick="closePopout()">×</button>');
                }
                $('#page-content-closable').show();
            }
        });

        // ✅ Update breadcrumbs with the corrected pathArray
        updateBreadcrumbs(pathArray);
    }
});



function closePopout(){
     $('#page-content-closable').hide();
}
