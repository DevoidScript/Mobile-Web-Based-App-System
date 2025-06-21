<?php
/**
 * FAQ Page
 * Path: templates/faq.php
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQs</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
            color: #333;
        }
        .header {
            display: flex;
            align-items: center;
            padding: 15px;
            background-color: #FF0000;
            color: white;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .back-arrow a {
            font-size: 24px;
            color: white;
            text-decoration: none;
        }
        .header-title {
            flex-grow: 1;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-right: 30px;
        }
        .container {
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        .faq-item {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            margin-bottom: 15px;
            padding: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .faq-question {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            font-weight: bold;
            color: #D50000;
        }
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            color: #666;
            margin-top: 0;
        }
        .faq-answer p {
            margin-top: 15px;
            margin-bottom: 0;
        }
        .arrow {
            font-size: 20px;
            transition: transform 0.3s;
        }
        .arrow.open {
            transform: rotate(180deg);
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="back-arrow">
            <a href="explore.php">&#8249;</a>
        </div>
        <div class="header-title">FAQs</div>
    </div>

    <div class="container">
        <div class="faq-item">
            <div class="faq-question">
                <span>Who can donate blood?</span>
                <span class="arrow">&#9662;</span>
            </div>
            <div class="faq-answer">
                <p>Anyone aged 16 to 65, in good health, and weighing at least 50kg may donate, provided they meet other eligibility criteria.</p>
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question">
                <span>How often can I donate blood?</span>
                <span class="arrow">&#9662;</span>
            </div>
            <div class="faq-answer">
                <p>You can donate blood every 3 months.</p>
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question">
                <span>Is donating blood safe?</span>
                <span class="arrow">&#9662;</span>
            </div>
            <div class="faq-answer">
                <p>Yes. All equipment used is sterile and disposable, and trained professionals ensure your safety throughout the process.</p>
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question">
                <span>How long does the blood donation process take?</span>
                <span class="arrow">&#9662;</span>
            </div>
            <div class="faq-answer">
                <p>The entire process takes about 30 to 45 minutes, while the actual donation only lasts 8 to 10 minutes.</p>
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question">
                <span>Do I need to fast before donating blood?</span>
                <span class="arrow">&#9662;</span>
            </div>
            <div class="faq-answer">
                <p>No. In fact, you should eat a healthy meal and drink plenty of water before donating.</p>
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question">
                <span>Can I donate blood if I have a tattoo or piercing?</span>
                <span class="arrow">&#9662;</span>
            </div>
            <div class="faq-answer">
                <p>Yes, as long as it's been done at a DOH-accredited facility and more than 12 months have passed.</p>
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question">
                <span>Will I feel weak after donating blood?</span>
                <span class="arrow">&#9662;</span>
            </div>
            <div class="faq-answer">
                <p>Most donors feel normal, but some may feel lightheaded. Rest, hydrate, and eat after donating.</p>
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question">
                <span>Can I still donate if I'm taking medication?</span>
                <span class="arrow">&#9662;</span>
            </div>
            <div class="faq-answer">
                <p>It depends on the medication. Consult a Red Cross staff member or physician for specific guidance.</p>
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question">
                <span>What happens to my blood after donation?</span>
                <span class="arrow">&#9662;</span>
            </div>
            <div class="faq-answer">
                <p>Your blood is tested, processed, and stored safely until it's matched and used for patients in need.</p>
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question">
                <span>Can I get infected from donating blood?</span>
                <span class="arrow">&#9662;</span>
            </div>
            <div class="faq-answer">
                <p>Absolutely not. Blood donation is 100% safe when done through accredited organizations like the Philippine Red Cross.</p>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.faq-question').forEach(item => {
            item.addEventListener('click', event => {
                const answer = item.nextElementSibling;
                const arrow = item.querySelector('.arrow');

                if (answer.style.maxHeight) {
                    answer.style.maxHeight = null;
                    answer.style.marginTop = '0';
                    arrow.classList.remove('open');
                } else {
                    answer.style.maxHeight = answer.scrollHeight + "px";
                    answer.style.marginTop = '15px';
                    arrow.classList.add('open');
                }
            });
        });
    </script>
</body>
</html> 