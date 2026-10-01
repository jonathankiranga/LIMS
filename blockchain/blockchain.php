<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Blockchain Viewer</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: "Segoe UI", sans-serif;
      font-size: 14px;
      background: #f9f9f9;
      color: #222;
      padding: 20px;
    }

    h2 {
      margin-bottom: 20px;
      color: #333;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      box-shadow: 0 0 5px rgba(0, 0, 0, 0.05);
    }

    th, td {
      padding: 10px 12px;
      border: 1px solid #ddd;
      text-align: left;
      word-break: break-word;
    }

    thead {
      background-color: #e0f7fa;
    }

    tbody tr:hover {
      background-color: #f1f1f1;
    }

    #pagination {
      display: flex;
      gap: 6px;
      margin-top: 20px;
      flex-wrap: wrap;
    }

    .pagination-link {
      padding: 6px 10px;
      background: #e0f2f1;
      border: 1px solid #b2dfdb;
      border-radius: 3px;
      color: #00796b;
      text-decoration: none;
      font-size: 13px;
      transition: background 0.2s;
    }

    .pagination-link:hover {
      background: #b2dfdb;
    }

    .pagination-link.active {
      background: #26a69a;
      color: #fff;
      font-weight: bold;
      pointer-events: none;
    }

    .loading {
      margin-top: 15px;
      color: #777;
      font-style: italic;
    }
  </style>
</head>
<body>

  <h2>📦 Blockchain Records</h2>

  <table id="blockchainTable">
    <thead>
      <tr>
        <th>Block ID</th>
        <th>Data</th>
        <th>Current Hash</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>

  <div id="pagination"></div>
  <div id="loadingMessage" class="loading" style="display: none;">Loading blockchain data...</div>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

  <script>
    $(function() {
      const itemsPerPage = 10;

      function loadBlockchain(page = 1) {
        $('#loadingMessage').show();
        $.ajax({
          url: 'ajax/ajax_blockchain.php',
          type: 'POST',
          data: { page: page },
          dataType: 'json',
          success: function(response) {
            const { blocks, totalPages, currentPage } = response;

            const tbody = $('#blockchainTable tbody');
            tbody.empty();

            blocks.forEach(block => {
              tbody.append(`
                <tr>
                  <td>${block.block_id}</td>
                  <td>${block.decrypted_data}</td>
                  <td>${block.current_hash}</td>
                </tr>
              `);
            });

            $('#pagination').empty();
            for (let i = 1; i <= totalPages; i++) {
              const active = i === currentPage ? 'active' : '';
              $('#pagination').append(`
                <a href="#" class="pagination-link ${active}" data-page="${i}">${i}</a>
              `);
            }

            $('#loadingMessage').hide();
          },
          error: function() {
            $('#blockchainTable tbody').html('<tr><td colspan="3">Error loading data</td></tr>');
            $('#loadingMessage').hide();
          }
        });
      }

      loadBlockchain(1);

      $(document).on('click', '.pagination-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        loadBlockchain(page);
      });
    });
  </script>

</body>
</html>
