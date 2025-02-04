/**
 * Class to persist the state of the application
 */
export default class Store {
  /**
   * Constructor
   * @param {string} name Name of the store
   * @param initialState
   */
  constructor (name, initialState = {}) {
    this.name = name;
    this.state = this.loadState(initialState);
  }

  /**
   * Load the state from the local storage
   * @param {object} initialState Initial state of the store
   * @returns {object} State of the store
   */
  loadState (initialState = {}) {
    try {
      const serializedState = localStorage.getItem(this.name);
      if (serializedState === null) {
        return initialState;
      }
      return JSON.parse(serializedState);
    } catch (error) {
      return initialState;
    }
  }

  hasState () {
    return Object.keys(this.state).length > 0;
  }

  /**
   * Save the state to the local storage
   * @param {object} state State of the store
   */
  saveState (state) {
    try {
      const serializedState = JSON.stringify(state);
      localStorage.setItem(this.name, serializedState);
    } catch (error) {
      // Ignore write errors
    }
  }

  /**
   * Get the state of the store
   * @returns {object} State of the store
   */
  getState () {
    return this.state;
  }

  /**
   * Update the state of the store
   * @param {object} state State of the store
   * @returns {object} Updated state of the store
   */
  setState (state) {
    this.state = state;
    this.saveState(state);
    return state;
  }

  /**
   * Clear the state of the store
   */
  clearState () {
    this.state = {};
    this.saveState({});
  }
}
