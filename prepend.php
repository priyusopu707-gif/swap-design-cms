<?php
/**
 * Swap Design - Dev Server Prepend
 *
 * Auto-loaded before every PHP request in the dev server.
 * Sets environment variables to work around bootstrap issues.
 */

putenv('APP_ENV=development');
putenv('APP_DEBUG=true');
putenv('APP_URL=http://localhost:8080');
