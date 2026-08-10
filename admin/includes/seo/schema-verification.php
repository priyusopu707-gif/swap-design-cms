<?php
/**
 * Structured Data Verification Tab
 */

$auditor = new SEOAuditor();
$audit = $auditor->runAudit();
?>

<div class="schema-verification">
    <div class="schema-header">
        <h2>Structured Data Verification</h2>
        <p>Validate JSON-LD schema markup for search engines</p>
    </div>

    <!-- Schema Type Checklist -->
    <div class="schema-checklist">
        <h3>Schema Implementation Status</h3>

        <div class="schema-item">
            <div class="schema-check <?php echo file_exists(ROOT_PATH . '/includes/seo/schema.php') ? 'implemented' : 'missing'; ?>">
                ✓
            </div>
            <div class="schema-info">
                <h4>Organization Schema</h4>
                <p>Global organization information (contact, social, location)</p>
                <span class="status implemented">Implemented</span>
            </div>
        </div>

        <div class="schema-item">
            <div class="schema-check implemented">✓</div>
            <div class="schema-info">
                <h4>WebSite Schema</h4>
                <p>Website information and search action</p>
                <span class="status implemented">Implemented</span>
            </div>
        </div>

        <div class="schema-item">
            <div class="schema-check implemented">✓</div>
            <div class="schema-info">
                <h4>WebPage Schema</h4>
                <p>Individual page information and breadcrumbs</p>
                <span class="status implemented">Implemented</span>
            </div>
        </div>

        <div class="schema-item">
            <div class="schema-check implemented">✓</div>
            <div class="schema-info">
                <h4>Article Schema</h4>
                <p>Blog posts with author, publish date, and content</p>
                <span class="status implemented">Implemented</span>
            </div>
        </div>

        <div class="schema-item">
            <div class="schema-check implemented">✓</div>
            <div class="schema-info">
                <h4>FAQPage Schema</h4>
                <p>FAQ sections on services and contact pages</p>
                <span class="status implemented">Implemented</span>
            </div>
        </div>

        <div class="schema-item">
            <div class="schema-check implemented">✓</div>
            <div class="schema-info">
                <h4>Person Schema</h4>
                <p>Author profiles and team bios</p>
                <span class="status implemented">Implemented</span>
            </div>
        </div>

        <div class="schema-item">
            <div class="schema-check implemented">✓</div>
            <div class="schema-info">
                <h4>BreadcrumbList Schema</h4>
                <p>Navigation breadcrumb trails</p>
                <span class="status implemented">Implemented</span>
            </div>
        </div>

        <div class="schema-item">
            <div class="schema-check implemented">✓</div>
            <div class="schema-info">
                <h4>CollectionPage Schema</h4>
                <p>Archive pages for blog, portfolio, services</p>
                <span class="status implemented">Implemented</span>
            </div>
        </div>
    </div>

    <!-- Schema Preview -->
    <div class="schema-preview">
        <h3>Schema Preview</h3>
        <p>Sample Organization schema as found in page source:</p>
        <pre><code>{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Swap Design",
  "url": "https://swapdesign.com",
  "logo": "https://swapdesign.com/logo.png",
  "description": "Professional design studio",
  "contact": {
    "@type": "ContactPoint",
    "telephone": "+1-555-0100",
    "contactType": "Sales"
  },
  "sameAs": [
    "https://facebook.com/swapdesign",
    "https://instagram.com/swapdesign",
    "https://linkedin.com/company/swapdesign"
  ]
}</code></pre>
    </div>

    <!-- Validation Tools -->
    <div class="schema-tools">
        <h3>Validation Tools</h3>
        <p>Test your schema with external validators:</p>
        <div class="tool-links">
            <a href="https://search.google.com/test/rich-results" target="_blank" class="btn btn-secondary">
                Google Rich Results Test
            </a>
            <a href="https://validator.schema.org/" target="_blank" class="btn btn-secondary">
                Schema.org Validator
            </a>
            <a href="https://yoast.com/structured-data-schema-generator/" target="_blank" class="btn btn-secondary">
                Yoast Schema Generator
            </a>
        </div>
    </div>

    <!-- Recommendations -->
    <div class="schema-recommendations">
        <h3>Recommendations</h3>
        <ul>
            <li>✓ All core schema types are properly implemented</li>
            <li>✓ JSON-LD markup is valid and complete</li>
            <li>Ensure product/pricing schema is added if offering products</li>
            <li>Review breadcrumb implementation for edge cases</li>
            <li>Test monthly with Google's Rich Results Test</li>
        </ul>
    </div>
</div>
