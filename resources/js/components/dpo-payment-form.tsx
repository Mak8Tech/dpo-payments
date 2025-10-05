import React, { useState, useEffect } from "react";
import {
  AlertCircle,
  Check,
  X,
  Loader2,
  CreditCard,
  Smartphone,
} from "lucide-react";

// Payment Form Component
export const DpoPaymentForm = ({
  amount: initialAmount = 0,
  defaultCountry = "ZM",
  defaultCurrency = "ZMW",
  onSuccess,
  onError,
  apiEndpoint = "/api/dpo/payments",
}) => {
  const [formData, setFormData] = useState({
    amount: initialAmount,
    country: defaultCountry,
    currency: defaultCurrency,
    customer_email: "",
    customer_name: "",
    customer_phone: "",
    payment_method: "card",
    description: "Payment",
  });

  const [countries, setCountries] = useState({});
  const [mobileProviders, setMobileProviders] = useState([]);
  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    fetchCountries();
  }, []);

  useEffect(() => {
    if (countries[formData.country]) {
      setFormData((prev) => ({
        ...prev,
        currency: countries[formData.country].currency,
      }));
      setMobileProviders(countries[formData.country].mobile_providers || []);
    }
  }, [formData.country, countries]);

  const fetchCountries = async () => {
    try {
      const response = await fetch("/api/dpo/countries");
      const data = await response.json();
      setCountries(data.countries || {});
    } catch (err) {
      console.error("Failed to fetch countries:", err);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setProcessing(true);
    setError("");

    try {
      const response = await fetch(apiEndpoint, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(formData),
      });

      const data = await response.json();

      if (data.success && data.payment_url) {
        if (onSuccess) {
          onSuccess(data);
        } else {
          window.location.href = data.payment_url;
        }
      } else {
        throw new Error(data.error || "Payment initialization failed");
      }
    } catch (err) {
      setError(err.message);
      if (onError) onError(err);
    } finally {
      setProcessing(false);
    }
  };

  const handleChange = (field, value) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const currencySymbol =
    countries[formData.country]?.currency || formData.currency;

  return (
    <div className="max-w-lg mx-auto p-6 bg-white rounded-lg shadow-lg">
      <h2 className="text-2xl font-bold text-gray-900 mb-6">Payment Details</h2>

      {error && (
        <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-md flex items-center">
          <AlertCircle className="h-5 w-5 text-red-600 mr-2" />
          <span className="text-red-800">{error}</span>
        </div>
      )}

      <form onSubmit={handleSubmit}>
        {/* Amount Input */}
        <div className="mb-4">
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Amount
          </label>
          <div className="relative">
            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">
              {currencySymbol}
            </span>
            <input
              type="number"
              step="0.01"
              min="0.01"
              required
              value={formData.amount}
              onChange={(e) =>
                handleChange("amount", parseFloat(e.target.value))
              }
              className="w-full pl-12 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>
        </div>

        {/* Country Selection */}
        <div className="mb-4">
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Country
          </label>
          <select
            value={formData.country}
            onChange={(e) => handleChange("country", e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
          >
            {Object.entries(countries).map(([code, country]) => (
              <option key={code} value={code}>
                {country.name}
              </option>
            ))}
          </select>
        </div>

        {/* Customer Email */}
        <div className="mb-4">
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Email Address
          </label>
          <input
            type="email"
            required
            value={formData.customer_email}
            onChange={(e) => handleChange("customer_email", e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
            placeholder="your@email.com"
          />
        </div>

        {/* Customer Name */}
        <div className="mb-4">
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Full Name
          </label>
          <input
            type="text"
            required
            value={formData.customer_name}
            onChange={(e) => handleChange("customer_name", e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
            placeholder="John Doe"
          />
        </div>

        {/* Customer Phone */}
        <div className="mb-4">
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Phone Number
          </label>
          <input
            type="tel"
            value={formData.customer_phone}
            onChange={(e) => handleChange("customer_phone", e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
            placeholder="+260 XXX XXX XXX"
          />
        </div>

        {/* Payment Method Selection */}
        {mobileProviders.length > 0 && (
          <div className="mb-6">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Payment Method
            </label>
            <div className="space-y-2">
              <label className="flex items-center p-3 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50">
                <input
                  type="radio"
                  value="card"
                  checked={formData.payment_method === "card"}
                  onChange={(e) =>
                    handleChange("payment_method", e.target.value)
                  }
                  className="mr-3"
                />
                <CreditCard className="h-5 w-5 mr-2 text-gray-600" />
                <span>Credit/Debit Card</span>
              </label>

              {mobileProviders.map((provider) => (
                <label
                  key={provider}
                  className="flex items-center p-3 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50"
                >
                  <input
                    type="radio"
                    value={provider}
                    checked={formData.payment_method === provider}
                    onChange={(e) =>
                      handleChange("payment_method", e.target.value)
                    }
                    className="mr-3"
                  />
                  <Smartphone className="h-5 w-5 mr-2 text-gray-600" />
                  <span>{provider}</span>
                </label>
              ))}
            </div>
          </div>
        )}

        {/* Submit Button */}
        <button
          type="submit"
          disabled={processing}
          className="w-full py-3 px-4 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center"
        >
          {processing ? (
            <>
              <Loader2 className="animate-spin h-5 w-5 mr-2" />
              Processing...
            </>
          ) : (
            "Pay Now"
          )}
        </button>
      </form>
    </div>
  );
};
