<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width,initial-scale=1" />
	<title>Preguntas - IA</title>
	<style>
		/* Estilo básico y centrado */
		body { font-family: Arial, sans-serif; background:#f4f6f8; margin:0; padding:0; display:flex; align-items:center; justify-content:center; min-height:100vh; }
		.container { background:white; width:100%; max-width:720px; border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,0.08); padding:20px; }
		h1 { margin:0 0 12px; font-size:20px; }
		textarea { width:100%; min-height:120px; resize:vertical; padding:10px; font-size:14px; border:1px solid #d1d5db; border-radius:4px; }
		.controls { display:flex; gap:10px; margin-top:10px; align-items:center; }
		button { background:#2563eb; color:white; border:none; padding:10px 14px; border-radius:6px; cursor:pointer; }
		button:disabled { background:#93c5fd; cursor:not-allowed; }
		.result { margin-top:16px; padding:12px; background:#f8fafc; border-radius:6px; white-space:pre-wrap; min-height:80px; border:1px solid #e6eef8; }
		.small { color:#6b7280; font-size:13px; }
	</style>
</head>
<body>
	<div class="container">
		<h1>Consulta con IA</h1>
		<p class="small">Escribe tu pregunta abajo y pulsa "Enviar".</p>

		<textarea id="question" placeholder="Escribe tu pregunta aquí..."></textarea>

		<div class="controls">
			<button id="send">Enviar</button>
			<span id="status" class="small"></span>
		</div>

		<div id="answer" class="result" aria-live="polite">La respuesta aparecerá aquí.</div>
	</div>

	<script>
		const sendBtn = document.getElementById('send');
		const questionEl = document.getElementById('question');
		const answerEl = document.getElementById('answer');
		const statusEl = document.getElementById('status');

		async function sendQuestion() {
			const q = questionEl.value.trim();
			if (!q) {
				answerEl.textContent = 'Por favor escribe una pregunta.';
				return;
			}
			sendBtn.disabled = true;
			statusEl.textContent = 'Enviando...';
			answerEl.textContent = '';

			try {
				const form = new FormData();
				form.append('question', q);

				const res = await fetch('/openai/respond', {
					method: 'POST',
					body: form,
					headers: { 'X-Requested-With': 'XMLHttpRequest' }
				});

				const data = await res.json();

				if (!res.ok || !data.success) {
					answerEl.textContent = data.error || 'Error en la petición';
				} else {
					answerEl.textContent = data.answer;
				}
			} catch (err) {
				answerEl.textContent = 'Error de red: ' + err.message;
			} finally {
				sendBtn.disabled = false;
				statusEl.textContent = '';
			}
		}

		sendBtn.addEventListener('click', sendQuestion);
		questionEl.addEventListener('keydown', (e) => {
			if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
				sendQuestion();
			}
		});
	</script>
</body>
</html>
