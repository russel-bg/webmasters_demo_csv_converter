<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-6">
          <h1 class="text-3xl font-bold text-gray-900">CSV to JSON Converter</h1>
          <div v-if="user" class="flex items-center space-x-4">
            <span class="text-gray-700">Welcome, {{ user.name }}</span>
            <button @click="logout" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
              Logout
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <!-- Login Form -->
      <div v-if="!user" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
          <h2 class="text-2xl font-bold mb-4">Login</h2>
          <form @submit.prevent="login" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Email</label>
              <input v-model="loginForm.email" type="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Password</label>
              <input v-model="loginForm.password" type="password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
              Login
            </button>
          </form>
          <div v-if="error" class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ error }}
          </div>
        </div>
      </div>

      <!-- Dashboard -->
      <div v-else class="space-y-6">
        <!-- Upload Form -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <h2 class="text-2xl font-bold mb-4">Upload CSV File</h2>
            <form @submit.prevent="uploadFile" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Document Name</label>
                <input v-model="uploadForm.name" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">CSV File</label>
                <input @change="handleFileSelect" type="file" accept=".csv" required class="mt-1 block w-full">
              </div>
              <div class="flex items-center">
                <input v-model="uploadForm.isPublic" type="checkbox" id="isPublic" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <label for="isPublic" class="ml-2 block text-sm text-gray-900">Make file public</label>
              </div>
              <button type="submit" :disabled="uploading" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50">
                {{ uploading ? 'Uploading...' : 'Upload File' }}
              </button>
            </form>
            <div v-if="uploadError" class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
              {{ uploadError }}
            </div>
            <div v-if="uploadSuccess" class="mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
              {{ uploadSuccess }}
            </div>
          </div>
        </div>

        <!-- Files List -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <h2 class="text-2xl font-bold mb-4">Files</h2>
            <div v-if="loading" class="text-center py-4">Loading...</div>
            <div v-else-if="files.length === 0" class="text-center py-4 text-gray-500">No files found</div>
            <div v-else class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Original File</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Records</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="file in files" :key="file.id">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ file.name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ file.original_filename }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="getStatusClass(file.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                        {{ file.status }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ file.records_count || '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ file.author || '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ file.created_at }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                      <button v-if="file.can_download" @click="downloadFile(file.id)" class="text-indigo-600 hover:text-indigo-900">
                        Download JSON
                      </button>
                      <button v-if="file.error_message" @click="showError(file.error_message)" class="text-red-600 hover:text-red-900">
                        View Error
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'App',
  data() {
    return {
      user: null,
      files: [],
      loading: false,
      uploading: false,
      error: '',
      uploadError: '',
      uploadSuccess: '',
      loginForm: {
        email: '',
        password: ''
      },
      uploadForm: {
        name: '',
        file: null,
        isPublic: false
      }
    }
  },
  async mounted() {
    await this.checkAuth();
    if (this.user) {
      await this.loadFiles();
    }
  },
  methods: {
    async checkAuth() {
      try {
        const response = await axios.get('/api/user');
        this.user = response.data.user;
      } catch (error) {
        this.user = null;
      }
    },
    async login() {
      try {
        this.error = '';
        const response = await axios.post('/api/login', this.loginForm);
        this.user = response.data.user;
        await this.loadFiles();
      } catch (error) {
        this.error = error.response?.data?.message || 'Login failed';
      }
    },
    async logout() {
      try {
        await axios.post('/api/logout');
        this.user = null;
        this.files = [];
      } catch (error) {
        console.error('Logout error:', error);
      }
    },
    handleFileSelect(event) {
      this.uploadForm.file = event.target.files[0];
    },
    async uploadFile() {
      if (!this.uploadForm.file) {
        this.uploadError = 'Please select a file';
        return;
      }

      try {
        this.uploading = true;
        this.uploadError = '';
        this.uploadSuccess = '';

        const formData = new FormData();
        formData.append('file', this.uploadForm.file);
        formData.append('name', this.uploadForm.name);
        formData.append('is_public', this.uploadForm.isPublic);

        const response = await axios.post('/api/files', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });

        this.uploadSuccess = response.data.message;
        this.uploadForm = { name: '', file: null, isPublic: false };
        await this.loadFiles();
      } catch (error) {
        this.uploadError = error.response?.data?.message || 'Upload failed';
      } finally {
        this.uploading = false;
      }
    },
    async loadFiles() {
      try {
        this.loading = true;
        const response = await axios.get('/api/files');
        this.files = response.data.files;
      } catch (error) {
        console.error('Error loading files:', error);
      } finally {
        this.loading = false;
      }
    },
    async downloadFile(fileId) {
      try {
        const response = await axios.get(`/api/files/${fileId}/download`);
        const dataStr = JSON.stringify(response.data.json, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = window.URL.createObjectURL(dataBlob);
        const link = document.createElement('a');
        link.href = url;
        link.download = response.data.filename;
        link.click();
        window.URL.revokeObjectURL(url);
      } catch (error) {
        console.error('Download error:', error);
      }
    },
    showError(message) {
      alert('Error: ' + message);
    },
    getStatusClass(status) {
      const classes = {
        'queued': 'bg-yellow-100 text-yellow-800',
        'processing': 'bg-blue-100 text-blue-800',
        'completed': 'bg-green-100 text-green-800',
        'failed': 'bg-red-100 text-red-800'
      };
      return classes[status] || 'bg-gray-100 text-gray-800';
    }
  }
}
</script> 