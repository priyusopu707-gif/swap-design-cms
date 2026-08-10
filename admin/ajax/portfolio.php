<?php
require_once __DIR__ . '/../includes/init.php';
Auth::require();

header('Content-Type: application/json');

/* CSRF protection */
$token = $_POST['token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCsrfToken($token)) {
    echo json_encode(['success'=>false,'error'=>'Invalid security token']);
    exit;
}

$manager = new PortfolioManager();
$action = $_POST['action'] ?? '';
$projectId = (int)($_POST['project_id'] ?? 0);

if (!$projectId && !in_array($action, ['reorder'])) {
    echo json_encode(['success'=>false,'error'=>'Project ID required']);
    exit;
}

try {
    switch ($action) {

        case 'save_gallery':
        case 'add_gallery':
            $id = (int)($_POST['id'] ?? 0);
            $galleryId = $manager->saveGalleryImage($projectId, [
                'image_url'  => $_POST['image_url'] ?? '',
                'caption'    => $_POST['caption'] ?? '',
                'image_type' => $_POST['image_type'] ?? 'general'
            ], $id > 0 ? $id : null);
            echo json_encode(['success'=>true, 'id'=>$galleryId]);
            break;

        case 'delete_gallery':
        case 'remove_gallery':
            $manager->deleteGalleryImage((int)($_POST['id']??0));
            echo json_encode(['success'=>true]);
            break;

        case 'reorder_gallery':
            $manager->reorderGalleryImages($projectId, json_decode($_POST['order']??'[]', true));
            echo json_encode(['success'=>true]);
            break;

        case 'save_faq':
        case 'add_faq':
            $id = (int)($_POST['id'] ?? 0);
            $faqId = $manager->saveFaq($projectId, [
                'question' => $_POST['question'] ?? '',
                'answer'   => $_POST['answer'] ?? ''
            ], $id > 0 ? $id : null);
            echo json_encode(['success'=>true, 'id'=>$faqId]);
            break;

        case 'delete_faq':
        case 'remove_faq':
            $manager->deleteFaq((int)($_POST['id']??0));
            echo json_encode(['success'=>true]);
            break;

        case 'link_testimonial':
            $manager->linkTestimonial($projectId, (int)($_POST['relation_id']??0));
            echo json_encode(['success'=>true]);
            break;

        case 'unlink_testimonial':
            $manager->unlinkTestimonial($projectId, (int)($_POST['relation_id']??0));
            echo json_encode(['success'=>true]);
            break;

        case 'link_service':
            $manager->linkService($projectId, (int)($_POST['relation_id']??0));
            echo json_encode(['success'=>true]);
            break;

        case 'unlink_service':
            $manager->unlinkService($projectId, (int)($_POST['relation_id']??0));
            echo json_encode(['success'=>true]);
            break;

        case 'link_block':
            $manager->linkBlock($projectId, (int)($_POST['relation_id']??0));
            echo json_encode(['success'=>true]);
            break;

        case 'unlink_block':
            $manager->unlinkBlock($projectId, (int)($_POST['relation_id']??0));
            echo json_encode(['success'=>true]);
            break;

        case 'reorder':
            $order = json_decode($_POST['order']??'[]', true);
            $manager->reorder($order);
            echo json_encode(['success'=>true]);
            break;

        case 'save_revision':
            $note = $_POST['revision_note'] ?? '';
            $revId = $manager->saveRevision($projectId, $note);
            echo json_encode(['success'=>true, 'id'=>$revId]);
            break;

        case 'get_revisions':
            $revs = $manager->getRevisions($projectId);
            echo json_encode(['success'=>true, 'revisions'=>$revs]);
            break;

        case 'restore_revision':
            $revisionId = (int)($_POST['revision_id'] ?? 0);
            $ok = $manager->restoreRevision($projectId, $revisionId);
            echo json_encode(['success'=>$ok]);
            break;

        case 'link_blog':
            $manager->linkBlog($projectId, (int)($_POST['relation_id']??0));
            echo json_encode(['success'=>true]);
            break;

        case 'unlink_blog':
            $manager->unlinkBlog($projectId, (int)($_POST['relation_id']??0));
            echo json_encode(['success'=>true]);
            break;

        default:
            echo json_encode(['success'=>false,'error'=>'Unknown action: '.$action]);
    }
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
