// MONIFY - Banking App with Backend
const MONIFY = {
    apiBase: '/chat/backend/api/',

    // Funciones de API
    async apiCall(endpoint, data) {
        try {
            const response = await fetch(this.apiBase + endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            // Intentar parsear la respuesta JSON aunque el status no sea OK
            let json = null;
            let text = null;
            try {
                text = await response.text();
                json = JSON.parse(text);
            } catch (e) {
                json = null;
            }

            if (!response.ok) {
                // Si el backend envía un mensaje en el body, usarlo
                if (json && json.message) {
                    throw new Error(json.message);
                } else if (text) {
                    // Si el backend devolvió texto plano, mostrarlo
                    throw new Error(text);
                } else {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
            }

            return json;
        } catch (error) {
            console.error('API Error:', error);
            // Re-lanzar el error tal cual para que la UI muestre el mensaje adecuado
            throw new Error(error.message || ('Error de conexi\u00f3n con el servidor: ' + error));
        }
    },

    // Autenticación
    async login(username, password) {
        const result = await this.apiCall('auth.php', {
            action: 'login',
            username: username,
            password: password
        });

        if (result.success) {
            localStorage.setItem('user', JSON.stringify(result.user));
            return result.user;
        } else {
            throw new Error(result.message);
        }
    },

    async register(userData) {
        const result = await this.apiCall('auth.php', {
            action: 'register',
            username: userData.username,
            email: userData.username + '@monify.com',
            password: userData.password,
            full_name: userData.fullName
        });

        if (result.success) {
            return await this.login(userData.username, userData.password);
        } else {
            throw new Error(result.message);
        }
    },

    async deposit(userId, amount) {
        const result = await this.apiCall('deposit.php', {
            user_id: userId,
            amount: amount
        });

        if (result.success) {
            const user = this.getCurrentUser();
            user.balance = result.new_balance;
            localStorage.setItem('user', JSON.stringify(user));
            return result.new_balance;
        } else {
            throw new Error(result.message);
        }
    },

    async withdraw(userId, amount) {
        const result = await this.apiCall('withdraw.php', {
            user_id: userId,
            amount: -amount
        });

        if (result.success) {
            const user = this.getCurrentUser();
            user.balance = result.new_balance;
            localStorage.setItem('user', JSON.stringify(user));
            return result.new_balance;
        } else {
            throw new Error(result.message);
        }
    },

    async transfer(userId, targetUsername, amount) {
        const result = await this.apiCall('transfer.php', {
            user_id: userId,
            target_username: targetUsername,
            amount: parseFloat(amount)
        });

        if (result.success) {
            const user = this.getCurrentUser();
            user.balance = result.new_balance;
            localStorage.setItem('user', JSON.stringify(user));
            return result;
        } else {
            throw new Error(result.message);
        }
    },

    // ⬇️⬇️⬇️ FUNCIÓN GET TRANSACTIONS - AGREGADA CORRECTAMENTE ⬇️⬇️⬇️
    async getTransactions(userId, filterType = 'all') {
        const result = await this.apiCall('transactions.php', {
            action: 'get_transactions',
            user_id: userId,
            filter_type: filterType
        });

        if (result.success) {
            return result.transactions;
        } else {
            throw new Error(result.message);
        }
    },

    // Helper functions
    getCurrentUser() {
        const userStr = localStorage.getItem('user');
        return userStr ? JSON.parse(userStr) : null;
    },

    logout() {
        localStorage.removeItem('user');
    },

    isAuthenticated() {
        return this.getCurrentUser() !== null;
    }
};

// Funciones globales
window.MONIFY = MONIFY;
window.login = MONIFY.login.bind(MONIFY);
window.registerUser = MONIFY.register.bind(MONIFY);
window.logout = MONIFY.logout.bind(MONIFY);

window.depositTo = function(username, amount) {
    const user = MONIFY.getCurrentUser();
    if (!user) throw new Error("Usuario no autenticado");
    return MONIFY.deposit(user.id, amount);
};

window.withdrawFrom = async function(username, amount) {
    const user = MONIFY.getCurrentUser();
    if (!user) throw new Error("Usuario no autenticado");
    try {
        return await MONIFY.withdraw(user.id, amount);
    } catch (ex) {
        // Propagar exactamente el mensaje del backend
        if (ex && ex.message) throw new Error(ex.message);
        throw ex;
    }
};

window.transferTo = function(targetUsername, amount) {
    const user = MONIFY.getCurrentUser();
    if (!user) throw new Error("Usuario no autenticado");
    return MONIFY.transfer(user.id, targetUsername, amount);
};

// Verificación de autenticación
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const protectedPages = ['home.html', 'deposit.html', 'withdraw.html', 'transfer.html', 'transactions.html', 'logout.html'];
    
    if (protectedPages.includes(currentPage) && !MONIFY.isAuthenticated()) {
        window.location.href = 'index.html';
    }
    
    if ((currentPage === 'index.html' || currentPage === 'register.html') && MONIFY.isAuthenticated()) {
        window.location.href = 'home.html';
    }
});