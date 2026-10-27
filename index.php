<?php
    require_once "config.php";
    require_once "functions.php";

    // 1. Handle Form Submissions (Process when POST data is received)
	if (isset($_POST['author_submit'])) {
		$authorName = $_POST['namn'];
		$authorBirthYear = $_POST['fodelsear'];

		if (addAuthor($authorName, $authorBirthYear, $pdo)) {
			$feedbackMessage = [1, "Författaren är skapad."];
		} else {
			$feedbackMessage = [0, "Något gick fel. Försök igen."];
		}
	}

	if (isset($_POST['book_submit'])) {
		$bookTitle = $_POST['titel'];
		$bookAuthorId = $_POST['forfattar_id'];
		$bookYear = $_POST['utgivningsar'];
		if (addBook($bookTitle, $bookAuthorId, $bookYear, $pdo)) {
			$feedbackMessage = [1, "Boken är skapad."];
		} else {
			$feedbackMessage = [0, "Något gick fel. Försök igen."];
		}
	}

    if (isset($_POST['borrower_submit'])) {
        $borrowerName = $_POST['lantagar_namn'];
        $memberSince = $_POST['medlem_since'];

        // Check if borrower already exists
        $stmt = $pdo->prepare("SELECT lantagar_id FROM lantagare WHERE namn = :namn LIMIT 1");
        $stmt->execute([':namn' => $borrowerName]);
        $existingBorrower = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingBorrower) {
            // Borrower already exists
            $feedbackMessage = [0, "Låntagaren finns redan."];
        } else {
            // Add new borrower
            $stmt = $pdo->prepare("INSERT INTO lantagare (namn, medlem_since) VALUES (:namn, :medlem_since)");
            if ($stmt->execute([':namn' => $borrowerName, ':medlem_since' => $memberSince])) {
                $feedbackMessage = [1, "Låntagaren är registrerad."];
            } else {
                $feedbackMessage = [0, "Något gick fel. Försök igen."];
            }
        }
    }

    if (isset($_POST['loan_submit'])) {
        $bookId = $_POST['bok_id'];
        $borrowerId = $_POST['lantagar_id'];
        $loanDate = $_POST['lan_datum'];

        if (loanBook($bookId, $borrowerId, $loanDate, $pdo)) {
            $feedbackMessage = [1, "Boken är lånad."];
        } else {
            $feedbackMessage = [0, "Något gick fel. Försök igen."];
        }
    }

    // 2. Fetch data for display
    $booksWithAuthorName = $pdo->query("
        SELECT * FROM bok 
        INNER JOIN forfattare 
        ON bok.forfattar_id = forfattare.forfattar_id
        ORDER BY bok.utgivningsar ASC;
    ")->fetchAll();

    // Fetch all authors for the Book Form dropdown
    $allAuthors = $pdo->query("SELECT * FROM forfattare ORDER BY namn ASC")->fetchAll();

    // Fetch all books for the Loan Form dropdown
    $allBooksForLoan = $pdo->query("SELECT * FROM bok ORDER BY titel ASC")->fetchAll();

    $allBorrowers = $pdo->query("SELECT * FROM lantagare ORDER BY namn ASC")->fetchAll();

    $allAvailableBooks = renderBookLoans(1, $pdo);

    $allLoanedBooks = renderBookLoans(0, $pdo);
?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bokbibliotek</title>
    
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">

    <!-- Semantic Header -->
    <header class="bg-white shadow-sm py-3 mb-4">
        <div class="container">
            <h1 class="h3 mb-0 text-dark fw-bold">
                <i class="bi bi-journal-richtext me-2 text-primary"></i>Sammis bibliotek
            </h1>
        </div>
    </header>

    <!-- Semantic Main Content -->
    <main>

	<div class="container my-3">
            <?php if (isset($feedbackMessage)): ?>
                <?php if ($feedbackMessage[0] === 1): ?>
                    <!-- Success Alert Box -->
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill fs-4 me-2"></i>
                            <div>
                                <strong>Klart!</strong> <?= htmlspecialchars($feedbackMessage[1]) ?>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php else: ?>
                    <!-- Danger Alert Box -->
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i>
                            <div>
                                <strong>Ett fel uppstod!</strong> <?= /* shorthand for echo*/ htmlspecialchars($feedbackMessage[1]) ?>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="container my-4">
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($booksWithAuthorName as $singleBook): ?>
                    <div class="col">
                        <article class="card h-100 shadow-sm border-0 rounded-3">
                            <!-- Placeholder cover header -->
                            <div class="card-header bg-primary text-white text-center py-4 rounded-top-3">
                                <i class="bi bi-book fs-1"></i>
                            </div>
                            
                            <div class="card-body d-flex flex-column">
                                <h2 class="card-title h5 fw-bold text-dark mb-2">
                                    <?= htmlspecialchars($singleBook['titel']) ?>
                                </h2>
                                
                                <p class="card-text text-muted mb-3">
                                    <i class="bi bi-person-fill me-1"></i>
                                    <strong>Författare:</strong> <?= htmlspecialchars($singleBook['namn']) ?>
                                </p>
                                
                                <div class="mt-auto pt-2 border-top">
                                    <small class="text-secondary">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        <strong>Utgivningsår:</strong> <?= htmlspecialchars($singleBook['utgivningsar']) ?>
                                    </small>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        
        <!-- Registered Loans Table -->
        <div class="container my-5">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-primary text-white py-3">
                    <h2 class="h5 mb-0">
                        <i class="bi bi-journal-check me-2"></i>Registrerade Lån
                    </h2>
                </div>

                <div class="card-body p-0">
                    <?php if ($allAvailableBooks === false): ?>

                        <div class="alert alert-danger m-3 mb-0" role="alert">
                            Kunde inte hämta tillgängliga böcker från databasen.
                        </div>

                    <?php elseif (empty($allAvailableBooks)): ?>

                        <div class="text-center text-muted py-5">
                            <i class="bi bi-journal-x fs-1 d-block mb-3"></i>
                            <p class="mb-0">Det finns inga tillgängliga böcker.</p>
                        </div>

                    <?php else: ?>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="ps-4">Boktitel</th>
                                        <th scope="col">Låntagare</th>
                                        <th scope="col">Lånedatum</th>
                                        <th scope="col">Återlämningsdatum</th>
                                        <th scope="col" class="pe-4">Status</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($allAvailableBooks as $book): ?>
                                        <tr>
                                            <td class="ps-4 fw-semibold">
                                                <?= htmlspecialchars($book['titel'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <i class="bi bi-person me-1 text-muted"></i>
                                                <?= htmlspecialchars($loan['namn'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($loan['lanedatum'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <?php if (!empty($loan['aterlamningsdatum'])): ?>
                                                    <?= htmlspecialchars($loan['aterlamningsdatum'], ENT_QUOTES, 'UTF-8') ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Inte återlämnad</span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="pe-4">
                                                <?php if (empty($loan['aterlamningsdatum'])): ?>
                                                    <span class="badge rounded-pill text-bg-warning">
                                                        <i class="bi bi-clock me-1"></i>Utlånad
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge rounded-pill text-bg-success">
                                                        <i class="bi bi-check-circle me-1"></i>Återlämnad
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php endif; ?>
                </div>

        <div class="container my-5">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-primary text-white py-3">
                    <h2 class="h5 mb-0">
                        <i class="bi bi-journal-check me-2"></i>Registrerade Lån
                    </h2>
                </div>

                <div class="card-body p-0">
                    <?php if ($allLoanedBooks === false): ?>

                        <div class="alert alert-danger m-3 mb-0" role="alert">
                            Kunde inte hämta tillgängliga böcker från databasen.
                        </div>

                    <?php elseif (empty($allLoanedBooks)): ?>

                        <div class="text-center text-muted py-5">
                            <i class="bi bi-journal-x fs-1 d-block mb-3"></i>
                            <p class="mb-0">Det finns inga registrerade lån.</p>
                        </div>

                    <?php else: ?>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="ps-4">Boktitel</th>
                                        <th scope="col">Låntagare</th>
                                        <th scope="col">Lånedatum</th>
                                        <th scope="col">Återlämningsdatum</th>
                                        <th scope="col" class="pe-4">Status</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($allLoanedBooks as $loan): ?>
                                        <tr>
                                            <td class="ps-4 fw-semibold">
                                                <?= htmlspecialchars($loan['titel'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <i class="bi bi-person me-1 text-muted"></i>
                                                <?= htmlspecialchars($loan['namn'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($loan['lanedatum'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <?php if (!empty($loan['aterlamningsdatum'])): ?>
                                                    <?= htmlspecialchars($loan['aterlamningsdatum'], ENT_QUOTES, 'UTF-8') ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Inte återlämnad</span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="pe-4">
                                                <?php if (empty($loan['aterlamningsdatum'])): ?>
                                                    <span class="badge rounded-pill text-bg-warning">
                                                        <i class="bi bi-clock me-1"></i>Utlånad
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge rounded-pill text-bg-success">
                                                        <i class="bi bi-check-circle me-1"></i>Återlämnad
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php endif; ?>
                </div>


                <div class="card-footer bg-white text-muted small py-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Visar alla registrerade lån, både aktiva och återlämnade.
                </div>
            </div>
        </div>


        <!-- Navigation Tabs -->
        <ul class="nav nav-pills mb-4 justify-content-center" id="libraryTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold" id="author-tab" data-bs-toggle="pill" data-bs-target="#author-content" type="button" role="tab">
                    <i class="bi bi-person-plus-fill me-1"></i> Lägg till Författare
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="book-tab" data-bs-toggle="pill" data-bs-target="#book-content" type="button" role="tab">
                    <i class="bi bi-book-half me-1"></i> Lägg till Bok
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold"
                        id="borrower-tab"
                        data-bs-toggle="pill"
                        data-bs-target="#borrower-content"
                        type="button"
                        role="tab">
                    <i class="bi bi-person-plus me-1"></i> Registrera Låntagare
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="loan-tab" data-bs-toggle="pill" data-bs-target="#loan-content" type="button" role="tab">
                    <i class="bi bi-arrow-left-right me-1"></i> Registrera Lån
                </button>
            </li>
          
        </ul>

        <!-- Tab Content -->
        <div class="tab-content container mb-5" id="libraryTabsContent">
            
            <!-- 1. ADD AUTHOR FORM -->
            <div class="tab-pane fade show active" id="author-content" role="tabpanel">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0 rounded-3">
                            <div class="card-header bg-primary text-white py-3">
                                <h2 class="h5 mb-0"><i class="bi bi-person-plus me-2"></i>Ny Författare</h2>
                            </div>
                            <div class="card-body p-4">
                                <form action="" method="POST">
                                    <div class="mb-3">
                                        <label for="authorName" class="form-label fw-semibold">Författarens namn</label>
                                        <input type="text" class="form-control" id="authorName" name="namn" placeholder="T.ex. Astrid Lindgren" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="authorBirthYear" class="form-label fw-semibold">Födelseår</label>
                                        <input type="number" class="form-control" id="authorBirthYear" name="fodelsear" placeholder="T.ex. 1907" min="1000" max="2099">
                                    </div>

                                    <button type="submit" name="author_submit" class="btn btn-primary w-100">Spara Författare</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. ADD BOOK FORM -->
            <div class="tab-pane fade" id="book-content" role="tabpanel">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0 rounded-3">
                            <div class="card-header bg-primary text-white py-3">
                                <h2 class="h5 mb-0"><i class="bi bi-book me-2"></i>Ny Bok</h2>
                            </div>
                            <div class="card-body p-4">
                                <form action="" method="POST">
                                    <div class="mb-3">
                                        <label for="bookTitle" class="form-label fw-semibold">Boktitel</label>
                                        <input type="text" class="form-control" id="bookTitle" name="titel" placeholder="T.ex. Mio min Mio" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="bookAuthor" class="form-label fw-semibold">Välj Författare</label>
                                        <select class="form-select" id="bookAuthor" name="forfattar_id" required>
                                            <option value="" selected disabled>Välj författare från listan...</option>
                                            <?php foreach ($allAuthors as $author): ?>
                                                <option value="<?= $author['forfattar_id'] ?>">
                                                    <?= htmlspecialchars($author['namn']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="bookYear" class="form-label fw-semibold">Utgivningsår</label>
                                        <input type="number" class="form-control" id="bookYear" name="utgivningsar" placeholder="T.ex. 1954" min="1000" max="2099" required>
                                    </div>
                                    <button type="submit" name="book_submit" class="btn btn-primary w-100">Spara Bok</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3 Registrera ny låntagare -->
       
            <div class="tab-pane fade" id="borrower-content" role="tabpanel">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0 rounded-3">
                            <div class="card-header bg-primary text-white py-3">
                                <h2 class="h5 mb-0">
                                    <i class="bi bi-person-plus me-2"></i>Registrera Låntagare
                                </h2>
                            </div>

                            <div class="card-body p-4">
                                <form action="" method="POST">
                                    <div class="mb-3">
                                        <label for="borrowerName" class="form-label fw-semibold">
                                            Låntagarens namn
                                        </label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="borrowerName"
                                            name="lantagar_namn"
                                            placeholder="T.ex. Anna Andersson"
                                            required
                                        >
                                    </div>

                                    <div class="mb-3">
                                        <label for="memberSince" class="form-label fw-semibold">
                                            Medlemsdatum
                                        </label>
                                        <input
                                            type="date"
                                            class="form-control"
                                            id="memberSince"
                                            name="medlem_since"
                                            value="<?= date('Y-m-d') ?>"
                                            required
                                        >
                                    </div>

                                    <button
                                        type="submit"
                                        name="borrower_submit"
                                        class="btn btn-primary w-100"
                                    >
                                        Registrera låntagare
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. REGISTER LOAN FORM -->
            <div class="tab-pane fade" id="loan-content" role="tabpanel">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0 rounded-3">
                            <div class="card-header bg-primary text-white py-3">
                                <h2 class="h5 mb-0"><i class="bi bi-journal-check me-2"></i>Vem lånar boken?</h2>
                            </div>
                            <div class="card-body p-4">
                                <form action="" method="POST">
                                    <div class="mb-3">
                                        <label for="selectBook" class="form-label fw-semibold">Välj Bok</label>
                                        <select class="form-select" id="selectBook" name="bok_id" required>
                                            <option value="" selected disabled>Välj vilken bok som lånas ut...</option>
                                            <?php foreach ($allBooksForLoan as $bookItem): ?>
                                                <option value="<?= $bookItem['bok_id'] ?>">
                                                    <?= htmlspecialchars($bookItem['titel']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="selectBorrower" class="form-label fw-semibold">
                                            Välj låntagare
                                        </label>

                                        <select class="form-select" id="selectBorrower" name="lantagar_id" required>
                                            <option value="" selected disabled>Välj låntagare från listan...</option>

                                            <?php foreach ($allBorrowers as $borrower): ?>
                                                <option value="<?= $borrower['lantagar_id'] ?>">
                                                    <?= htmlspecialchars($borrower['namn'], ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="loanDate" class="form-label fw-semibold">Lånedatum</label>
                                        <input type="date" class="form-control" id="loanDate" name="lan_datum" required>
                                    </div>
                                    <button type="submit" name="loan_submit" class="btn btn-primary w-100">Registrera Lån</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Semantic Footer -->
    <footer class="text-center py-4 text-muted border-top mt-5 bg-white">
        <div class="container">
            <small>&copy; <?= date('Y') ?> Bokbibliotek. Alla rättigheter förbehållna.</small>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>