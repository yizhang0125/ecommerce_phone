<?php
// If you want to pre-fill card number, card holder, and expiration date, you can set default values here
$cardNumber = isset($_POST['cardNumber']) ? $_POST['cardNumber'] : '1234 5678 9012 3456';
$cardHolder = isset($_POST['cardHolder']) ? $_POST['cardHolder'] : 'John Doe';
$cardExpiry = isset($_POST['cardExpiry']) ? $_POST['cardExpiry'] : '02/24';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visa Bank Card</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* General Styling */
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 1rem;
        }

        .container {
            text-align: center;
            width: 100%;
            max-width: 500px;
        }

        .bank-card {
            width: 90%;
            max-width: 450px;
            height: 250px;
            background: linear-gradient(135deg, #007bff, #00d4ff);
            border-radius: 15px;
            color: white;
            padding: 20px;
            position: relative;
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            margin: 0 auto;
        }

        .bank-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .bank-card-header img {
            height: 35px;
        }

        .bank-card-chip {
            margin-top: 10%;
            margin-left: 10%;
        }

        .bank-card-chip img {
            width: 50px;
        }

        .bank-card-logo {
            margin-top: -12%;
        }

        .bank-card-logo img {
            height: 30px;
        }

        .bank-card-number {
            font-size: 1.8rem;
            letter-spacing: 2.5px;
            text-align: center;
            margin-top: -40px; /* Decrease this value to move it up */
        }

        .bank-card-number input {
            background: transparent;
            border: none;
            color: white;
            font-size: 1.8rem;
            text-align: center;
            letter-spacing: 2.5px;
            outline: none;
            width: 100%;
        }

        .bank-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Move Expiry and Cardholder Inputs */
        .bank-card-footer input {
            background: transparent;
            border: none;
            color: white;
            font-size: 1.1rem;
            font-weight: bold;
            outline: none;
            text-align: left;
            width: 200px;
        }
        #cardExpiryInput {
            margin-left: 140px;
        }

        /* Cardholder input */
        .bank-card-holder {
            font-size: 1.1rem;
            font-weight: bold;
            border: none;
            background: transparent;
            color: white;
            text-align: left;  /* Align name to the left */
            width: 100%;
        }

        .btn-success {
            font-size: 1.1rem;
            padding: 10px 25px;
            width: auto;
            border-radius: 8px;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .bank-card {
                height: 220px;
            }

            .bank-card-chip img {
                width: 45px;
            }

            .bank-card-number input {
                font-size: 1.5rem;
            }

            .bank-card-footer input {
                width: 150px;
            }

            #cardExpiryInput {
                margin-left: 120px;
            }
        }

        @media (max-width: 576px) {
            .bank-card {
                height: 200px;
                padding: 15px;
            }

            .bank-card-chip img {
                width: 40px;
            }

            .bank-card-logo img {
                height: 25px;
            }

            .bank-card-number input {
                font-size: 1.2rem;
            }

            .bank-card-footer input {
                width: 120px;
                font-size: 0.9rem;
            }

            #cardExpiryInput {
                margin-left: 100px;
            }
        }

        @media (max-width: 375px) {
            .bank-card {
                height: 180px;
                padding: 12px;
            }

            .bank-card-chip img {
                width: 35px;
            }

            .bank-card-logo img {
                height: 20px;
            }

            .bank-card-number input {
                font-size: 1rem;
                letter-spacing: 2px;
            }

            .bank-card-footer input {
                width: 100px;
                font-size: 0.8rem;
            }

            #cardExpiryInput {
                margin-left: 85px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Visa Bank Card</h1>
    <div class="bank-card mx-auto mt-4">
        <div class="bank-card-header">
            <div class="bank-card-chip">
                <img src="https://raw.githubusercontent.com/muhammederdem/credit-card-form/master/src/assets/images/chip.png" alt="Chip">
            </div>
            <div class="bank-card-logo">
                <img src="https://upload.wikimedia.org/wikipedia/commons/4/41/Visa_Logo.png" alt="Visa Logo">
            </div>
        </div>
        <div class="bank-card-number">
            <input type="text" id="cardNumberInput" value="<?php echo $cardNumber; ?>" maxlength="19" 
                   oninput="updateHiddenInput()">
        </div>
        <div class="bank-card-footer">
            <!-- Swapped the order of expiry and card holder -->
            <input 
                type="text" 
                id="cardHolderInput" 
                value="<?php echo $cardHolder; ?>" 
                oninput="updateCardHolder()" 
            />
            <input 
                type="text" 
                id="cardExpiryInput" 
                value="<?php echo $cardExpiry; ?>" 
                maxlength="5" 
                oninput="updateExpiry()"
            />
        </div>
    </div>
    <form id="paymentForm" method="POST" action="process_payment.php" class="mt-3">
        <input type="hidden" name="order_id" value="<?php echo $_GET['order_id']; ?>">
        <input type="hidden" name="cardNumber" id="hiddenCardNumber" value="<?php echo $cardNumber; ?>">
        <input type="hidden" name="cardHolder" id="hiddenCardHolder" value="<?php echo $cardHolder; ?>">
        <input type="hidden" name="cardExpiry" id="hiddenCardExpiry" value="<?php echo $cardExpiry; ?>">
        <button type="submit" class="btn btn-success">Check Out</button>
    </form>
</div>

<script>
function updateHiddenInput() {
    const cardNumberInput = document.getElementById('cardNumberInput');
    const hiddenCardNumber = document.getElementById('hiddenCardNumber');

    // Allow only digits and spaces
    cardNumberInput.value = cardNumberInput.value.replace(/[^0-9 ]/g, '');

    // Update hidden input value
    hiddenCardNumber.value = cardNumberInput.value;

    // Auto-format as 4-digit blocks
    const rawValue = cardNumberInput.value.replace(/\s+/g, ''); 
    let formattedValue = '';
    for (let i = 0; i < rawValue.length; i++) {
        if (i > 0 && i % 4 === 0) formattedValue += ' ';
        formattedValue += rawValue[i];
    }
    cardNumberInput.value = formattedValue;
}

function updateExpiry() {
    const cardExpiryInput = document.getElementById('cardExpiryInput');
    const hiddenCardExpiry = document.getElementById('hiddenCardExpiry');

    // Allow only digits and "/"
    cardExpiryInput.value = cardExpiryInput.value.replace(/[^0-9\/]/g, '');

    // Auto-format as MM/YY
    const rawValue = cardExpiryInput.value.replace(/\//g, '');
    let formattedValue = '';
    for (let i = 0; i < rawValue.length; i++) {
        if (i === 2) formattedValue += '/';
        formattedValue += rawValue[i];
    }
    cardExpiryInput.value = formattedValue.substring(0, 5); // Limit to MM/YY
    hiddenCardExpiry.value = cardExpiryInput.value;
}

function updateCardHolder() {
    const cardHolderInput = document.getElementById('cardHolderInput');
    const hiddenCardHolder = document.getElementById('hiddenCardHolder');

    // Update hidden input value
    hiddenCardHolder.value = cardHolderInput.value;
}
</script>
</body>
</html>
