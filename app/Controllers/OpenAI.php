<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class OpenAI extends Controller
{
	// Muestra la página con el formulario
	public function index()
	{
		return view('ai_chat');
	}

	// Endpoint AJAX: recibe 'question' y consulta Azure OpenAI
	public function respond()
	{
		$request = service('request');
		$question = $request->getPost('question');

		if (empty($question)) {
			return $this->response->setJSON(['success' => false, 'error' => 'Pregunta vacía'])->setStatusCode(400);
		}

		$endpoint   = getenv('AZURE_OPENAI_ENDPOINT');
		$apiKey     = getenv('AZURE_OPENAI_API_KEY'); // CORRECCIÓN: leer la variable de entorno adecuada
		$deployment = getenv('AZURE_OPENAI_DEPLOYMENT');
		$apiVersion = getenv('AZURE_OPENAI_API_VERSION') ?: '2023-05-15';

		if (empty($endpoint) || empty($apiKey) || empty($deployment)) {
			return $this->response->setJSON(['success' => false, 'error' => 'Configuración de Azure OpenAI no encontrada'])->setStatusCode(500);
		}

		$url = rtrim($endpoint, '/') . "/openai/deployments/{$deployment}/chat/completions?api-version={$apiVersion}";

		$payload = [
			'messages' => [
				['role' => 'user', 'content' => $question]
			],
			'max_tokens' => 800
		];

		$ch = curl_init($url);
		$headers = [
			'Content-Type: application/json',
			'api-key: ' . $apiKey
		];

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);

		$result = curl_exec($ch);
		$err = curl_error($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($result === false) {
			return $this->response->setJSON(['success' => false, 'error' => 'cURL error: ' . $err])->setStatusCode(500);
		}

		$data = json_decode($result, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return $this->response->setJSON(['success' => false, 'error' => 'Respuesta inválida de Azure OpenAI'])->setStatusCode(500);
		}

		// Extraer respuesta (según formato chat)
		$answer = '';
		if (isset($data['choices'][0]['message']['content'])) {
			$answer = $data['choices'][0]['message']['content'];
		} elseif (isset($data['choices'][0]['text'])) {
			$answer = $data['choices'][0]['text'];
		} else {
			$answer = json_encode($data);
		}

		return $this->response->setJSON(['success' => true, 'answer' => $answer])->setStatusCode($httpCode ?: 200);
	}

	// Nuevo endpoint: recibe 'message' y devuelve texto plano (implementación según tu ejemplo)
	public function chat()
	{
		$request = service('request');
		$userMessage = $request->getPost('message');

		if (empty($userMessage)) {
			return $this->response->setStatusCode(400)->setBody('Mensaje vacío');
		}

    $endpoint   = getenv('AZURE_OPENAI_ENDPOINT');
    $apiKey     = getenv('AZURE_OPENAI_KEY');
    $deployment = getenv('AZURE_OPENAI_DEPLOYMENT');
    $apiVersion = getenv('AZURE_OPENAI_API_VERSION') ?: '2024-02-01';

		if (empty($endpoint) || empty($apiKey) || empty($deployment)) {
			return $this->response->setStatusCode(500)->setBody('Configuración de Azure OpenAI no encontrada');
		}

		$url = rtrim($endpoint, '/') . "/openai/deployments/{$deployment}/chat/completions?api-version={$apiVersion}";

		$data = [
			'messages' => [
				['role' => 'user', 'content' => $userMessage]
			],
			'max_tokens' => 150
		];

		// Intentar con file_get_contents (stream context), fallback a cURL
		$options = [
			'http' => [
				'header'  => "Content-Type: application/json\r\n" . "api-key: {$apiKey}\r\n",
				'method'  => 'POST',
				'content' => json_encode($data),
				'timeout' => 30
			]
		];

		$context = stream_context_create($options);
		$result = @file_get_contents($url, false, $context);

		if ($result === false) {
			// Fallback a cURL
			$ch = curl_init($url);
			$headers = [
				'Content-Type: application/json',
				'api-key: ' . $apiKey
			];
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			$result = curl_exec($ch);
			$err = curl_error($ch);
			curl_close($ch);

			if ($result === false) {
				return $this->response->setStatusCode(500)->setBody('Error al comunicarse con la API: ' . $err);
			}
		}

		$responseData = json_decode($result, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return $this->response->setStatusCode(500)->setBody('Respuesta inválida de la API');
		}

		$botReply = '';
		if (isset($responseData['choices'][0]['message']['content'])) {
			$botReply = $responseData['choices'][0]['message']['content'];
		} elseif (isset($responseData['choices'][0]['text'])) {
			$botReply = $responseData['choices'][0]['text'];
		} else {
			$botReply = json_encode($responseData);
		}

		return $this->response->setStatusCode(200)->setBody($botReply);
	}
}