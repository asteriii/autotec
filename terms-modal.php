<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms Modal - Autotec Shaw Branch</title>
</head>
<body>

    <!-- Terms Modal -->
    <div id="termsModal" class="terms-modal">
        <div class="terms-modal-content">
            <div class="terms-modal-header">
                <h2>Terms and Conditions</h2>
                <span class="terms-modal-close">&times;</span>
            </div>
            <div class="terms-modal-body">
                <div class="terms-content">
                    <h3>1. Service Agreement</h3>
                    <p>Welcome to Autotec Shaw Branch. By utilizing our automotive services, you agree to comply with and be bound by the following terms and conditions. Please review these terms carefully before using our services.</p>
                    <p>These terms constitute a legally binding agreement between you (the customer) and Autotec Shaw Branch regarding the use of our automotive repair and maintenance services.</p>

                    <h3>2. Services Provided</h3>
                    <p>Autotec Shaw Branch provides the following automotive services:</p>
                    <ul>
                        <li>Vehicle diagnostic and repair services</li>
                        <li>Routine maintenance and inspection</li>
                        <li>Parts replacement and installation</li>
                        <li>Emergency roadside assistance (where applicable)</li>
                        <li>Vehicle registration assistance</li>
                    </ul>

                    <h3>3. Customer Responsibilities</h3>
                    <p>As a customer, you agree to:</p>
                    <ul>
                        <li>Provide accurate information about your vehicle and its condition</li>
                        <li>Pay all fees and charges in accordance with our payment terms</li>
                        <li>Pick up your vehicle within the agreed timeframe</li>
                        <li>Comply with all safety regulations while on our premises</li>
                        <li>Maintain proper insurance coverage for your vehicle</li>
                    </ul>

                    <h3>4. Payment Terms</h3>
                    <p>Payment for services rendered is due upon completion of work unless other arrangements have been made in writing. We accept cash, major credit cards, and approved financing options.</p>

                    <h3>5. Privacy Policy</h3>
                    <p>We respect your privacy and are committed to protecting your personal information. Customer information is kept confidential and used only for service-related purposes.</p>

                    <h3>6. Contact Information</h3>
                    <p>For questions regarding these terms or our services, please contact us:</p>
                    <ul>
                        <li><strong>Phone:</strong> 286527257</li>
                        <li><strong>Email:</strong> autotec_mandaluyong@yahoo.com</li>
                        <li><strong>Facebook:</strong> AutotecShawPH</li>
                        <li><strong>Location:</strong> Shaw Branch, Mandaluyong</li>
                    </ul>

                    <div class="footer-highlight">
                        <p><strong>By using our services, you acknowledge that you have read, understood, and agree to be bound by these terms and conditions.</strong></p>
                    </div>
                </div>
            </div>
            <div class="terms-modal-footer">
                <button class="terms-btn-close">Close</button>
            </div>
        </div>
    </div>

    <style>
        /* General body styling */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }

        /* Terms Modal Styles */
        .terms-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            animation: termsModalFadeIn 0.3s ease-out;
        }

        .terms-modal-content {
            background-color: #fff;
            margin: 2% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            animation: termsModalSlideIn 0.3s ease-out;
            overflow: hidden;
        }

        .terms-modal-header {
            background: linear-gradient(135deg, #a4133c 0%, #ff758f 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .terms-modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .terms-modal-close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            line-height: 1;
        }

        .terms-modal-close:hover {
            color: #f0f0f0;
            transform: scale(1.1);
        }

        .terms-modal-body {
            padding: 30px;
            max-height: 60vh;
            overflow-y: auto;
        }

        .terms-content h3 {
            color: #333;
            margin: 25px 0 10px 0;
            font-size: 1.1rem;
            font-weight: 600;
            border-bottom: 2px solid #a4133c;
            padding-bottom: 5px;
        }

        .terms-content h3:first-child {
            margin-top: 0;
        }

        .terms-content p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
            text-align: justify;
        }

        .terms-content ul {
            color: #666;
            line-height: 1.6;
            margin: 15px 0;
            padding-left: 20px;
        }

        .terms-content li {
            margin-bottom: 5px;
        }

        .footer-highlight {
            background-color: #f8f9ff;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #a4133c;
            margin: 20px 0;
        }

        .footer-highlight p {
            margin: 0;
            color: #333 !important;
        }

        .terms-last-updated {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #888 !important;
            font-size: 0.9rem;
            text-align: center !important;
        }

        .terms-modal-footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: right;
            border-top: 1px solid #eee;
        }

        .terms-btn-close {
            background: linear-gradient(135deg,  #a4133c 0%, #ff758f 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .terms-btn-close:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(234, 102, 131, 0.4);
        }

        /* Terms Modal Animations */
        @keyframes termsModalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes termsModalSlideIn {
            from { 
                opacity: 0;
                transform: translateY(-50px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Terms Modal Scrollbar styling */
        .terms-modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .terms-modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .terms-modal-body::-webkit-scrollbar-thumb {
            background: #ff758f;
            border-radius: 4px;
        }

        .terms-modal-body::-webkit-scrollbar-thumb:hover {
            background: #ff758f;
        }

        /* Terms Modal Responsive */
        @media (max-width: 768px) {
            .terms-modal-content {
                margin: 5% auto;
                width: 95%;
            }
            
            .terms-modal-header, .terms-modal-body, .terms-modal-footer {
                padding: 20px;
            }
            
            .terms-modal-body {
                max-height: 50vh;
            }
        }
    </style>

    <script>
        // Terms Modal Functions
        function openTermsModal() {
            document.getElementById('termsModal').style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }

        function closeTermsModal() {
            document.getElementById('termsModal').style.display = 'none';
            document.body.style.overflow = 'auto'; // Restore scrolling
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('termsModal');
            if (event.target == modal) {
                closeTermsModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeTermsModal();
            }
        });

        // Initialize modal functionality when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Close button functionality
            const closeBtn = document.querySelector('.terms-modal-close');
            const closeFooterBtn = document.querySelector('.terms-btn-close');
            
            if (closeBtn) {
                closeBtn.onclick = closeTermsModal;
            }
            
            if (closeFooterBtn) {
                closeFooterBtn.onclick = closeTermsModal;
            }
        });
    </script>
</body>
</html>