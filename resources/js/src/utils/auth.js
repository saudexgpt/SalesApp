// import Cookies from 'js-cookie'

const TokenKey = 'accessToken'

export function getToken () {
  // return Cookies.get(TokenKey)
  return localStorage.getItem(TokenKey)
}

export function setToken (token) {
  // return Cookies.set(TokenKey, token)
  return localStorage.setItem(TokenKey, token)
}

export function removeToken () {
  // return Cookies.remove(TokenKey)
  localStorage.removeItem('loggedIn')
  localStorage.removeItem('userInfo')
  localStorage.removeItem(TokenKey)
}
