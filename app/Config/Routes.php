<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->get('/openai', 'OpenAI::index');
$routes->post('/openai/respond', 'OpenAI::respond');
$routes->post('/openai/chat', 'OpenAI::chat');
