function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function defaultHeaders(isFormData = false) {
    const headers = {
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };
    if (!isFormData) {
        headers['Content-Type'] = 'application/json';
    }
    return headers;
}

async function request(method, url, body = null) {
    const isFormData = body instanceof FormData;

    // PHP never populates $_POST/$_FILES for multipart bodies on PUT/PATCH —
    // only true POST requests get parsed. Spoof via _method so Laravel still
    // routes/validates as PUT/PATCH while the wire request stays a real POST.
    if (isFormData && (method === 'PUT' || method === 'PATCH')) {
        body.append('_method', method);
        method = 'POST';
    }

    const options = {
        method,
        headers: defaultHeaders(isFormData),
        credentials: 'same-origin',
    };

    if (body !== null) {
        options.body = isFormData ? body : JSON.stringify(body);
    }

    const response = await fetch(`/api${url}`, options);

    if (response.status === 204) return null;

    const json = await response.json();

    if (!response.ok) {
        const error = new Error(json.message ?? 'Request failed');
        error.status = response.status;
        error.errors = json.errors ?? null;
        throw error;
    }

    return json;
}

export const api = {
    get:    (url)       => request('GET',    url),
    post:   (url, body) => request('POST',   url, body),
    put:    (url, body) => request('PUT',    url, body),
    patch:  (url, body) => request('PATCH',  url, body),
    delete: (url)       => request('DELETE', url),
};
